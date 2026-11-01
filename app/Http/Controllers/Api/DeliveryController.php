<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DeliveryController extends Controller
{
    /**
     * Get orders for the delivery courier / rider
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Order::with(['customer', 'items', 'store', 'deliveryRider'])
            ->latest();

        // If rider is logged in, filter to their deliveries or show active dispatchable orders
        if ($user && in_array($user->role, ['delivery', 'courier'])) {
            $statusFilter = $request->get('tab', 'active'); // 'active', 'delivered', 'all'
            
            $query->where(function ($q) use ($user) {
                $q->where('delivery_user_id', $user->id)
                  ->orWhere(function ($sub) {
                      $sub->whereNull('delivery_user_id')
                          ->whereIn('status', ['processing', 'shipped']);
                  });
            });

            if ($statusFilter === 'delivered') {
                $query->where('delivery_status', 'delivered');
            } elseif ($statusFilter === 'active') {
                $query->whereIn('delivery_status', ['unassigned', 'assigned', 'out_for_delivery']);
            }
        }

        $orders = $query->paginate(30);

        return response()->json($orders);
    }

    /**
     * Update delivery status by rider
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'delivery_status' => 'required|in:assigned,out_for_delivery,delivered,failed',
            'delivery_notes' => 'nullable|string',
        ]);

        $order = Order::findOrFail($id);
        $user = $request->user();

        // Assign to current rider if not assigned
        if (!$order->delivery_user_id && $user && in_array($user->role, ['delivery', 'courier'])) {
            $order->delivery_user_id = $user->id;
        }

        $order->delivery_status = $validated['delivery_status'];
        if (isset($validated['delivery_notes'])) {
            $order->delivery_notes = $validated['delivery_notes'];
        }

        // Keep parent order status synced with delivery workflow
        if ($validated['delivery_status'] === 'delivered') {
            $order->status = 'delivered';
            $order->delivered_at = Carbon::now();
        } elseif ($validated['delivery_status'] === 'out_for_delivery') {
            $order->status = 'shipped';
        }

        $order->save();

        return response()->json([
            'success' => true,
            'message' => "Order #{$order->order_number} delivery status updated to {$order->delivery_status}",
            'order' => $order->fresh(['customer', 'items', 'store', 'deliveryRider']),
        ]);
    }

    /**
     * Summary stats for rider dashboard
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $riderId = $user ? $user->id : null;

        $baseQuery = Order::query();
        if ($riderId) {
            $baseQuery->where('delivery_user_id', $riderId);
        }

        $totalAssigned = (clone $baseQuery)->count();
        $activeCount = (clone $baseQuery)->whereIn('delivery_status', ['assigned', 'out_for_delivery'])->count();
        
        $todayDeliveredQuery = (clone $baseQuery)
            ->where('delivery_status', 'delivered')
            ->where(function ($q) {
                $q->whereDate('delivered_at', Carbon::today())
                  ->orWhere(function ($sub) {
                      $sub->whereNull('delivered_at')->whereDate('updated_at', Carbon::today());
                  });
            });

        $deliveredToday = (clone $todayDeliveredQuery)->count();

        // Cash to collect (COD on active assigned orders)
        $cashToCollect = (clone $baseQuery)
            ->where('payment_method', 'cod')
            ->whereIn('delivery_status', ['assigned', 'out_for_delivery'])
            ->sum('final_amount');

        // Cash collected today (COD orders delivered today)
        $cashCollectedToday = (clone $todayDeliveredQuery)
            ->where('payment_method', 'cod')
            ->sum('final_amount');

        $codDeliveredTodayCount = (clone $todayDeliveredQuery)
            ->where('payment_method', 'cod')
            ->count();

        $prepaidDeliveredTodayCount = (clone $todayDeliveredQuery)
            ->where('payment_method', '!=', 'cod')
            ->count();

        return response()->json([
            'total_assigned' => $totalAssigned,
            'active_deliveries' => $activeCount,
            'delivered_today' => $deliveredToday,
            'cod_delivered_today' => $codDeliveredTodayCount,
            'prepaid_delivered_today' => $prepaidDeliveredTodayCount,
            'cash_to_collect' => round((float) $cashToCollect, 2),
            'cash_collected_today' => round((float) $cashCollectedToday, 2),
            'total_shift_cash' => round((float) ($cashCollectedToday + $cashToCollect), 2),
        ]);
    }
}
