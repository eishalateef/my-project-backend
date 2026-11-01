<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Get orders for the authenticated customer / shopper
     */
    public function orders(Request $request)
    {
        $user = $request->user();

        if (!$user && $request->filled('user_id')) {
            $user = User::find($request->user_id);
        }

        if (!$user && $request->filled('email')) {
            $user = User::where('email', $request->email)->first();
        }

        if (!$user) {
            if ($request->filled('phone')) {
                $orders = Order::with(['items', 'store', 'deliveryRider'])
                    ->where('phone', preg_replace('/\s+/', '', $request->phone))
                    ->latest()
                    ->paginate(15);
                return response()->json($orders);
            }
            return response()->json(['data' => []]);
        }

        $orders = Order::with(['items', 'store', 'deliveryRider'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere(function ($sub) use ($user) {
                      $sub->whereHas('customer', function ($cq) use ($user) {
                          if (!empty($user->email)) {
                              $cq->where('email', $user->email);
                          }
                          if (!empty($user->phone)) {
                              $cq->orWhere('phone', $user->phone);
                          }
                      });
                  });
            })
            ->latest()
            ->paginate(15);

        return response()->json($orders);
    }

    /**
     * Update customer profile / default shipping info
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        if (!$user && $request->filled('user_id')) {
            $user = User::find($request->user_id);
        }

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:25',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Customer cancels an order (allowed only if pending or processing)
     */
    public function cancelOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if (in_array($order->status, ['shipped', 'delivered'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel order that has already been dispatched or delivered.',
            ], 422);
        }

        if ($order->status === 'cancelled') {
            return response()->json([
                'success' => true,
                'message' => 'Order is already cancelled.',
                'order' => $order,
            ]);
        }

        $order->status = 'cancelled';
        $order->delivery_status = 'failed';
        $order->save();

        // Restore stock
        foreach ($order->items as $item) {
            \App\Models\Product::where('id', $item->product_id)->increment('stock', $item->quantity);
        }

        return response()->json([
            'success' => true,
            'message' => "Order #{$order->order_number} has been cancelled successfully.",
            'order' => $order->fresh(['items', 'store', 'deliveryRider']),
        ]);
    }
}
