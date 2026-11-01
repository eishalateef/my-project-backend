<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Check if the current request has developer master privileges
     */
    protected function isDeveloperAuthorized(Request $request): bool
    {
        $devKey = $request->header('X-Developer-Key') ?? $request->query('dev_key') ?? $request->input('dev_key');
        $secret = config('flexistore.developer_secret_key', 'flexi_dev_secret_2026');
        return !empty($devKey) && hash_equals($secret, (string) $devKey);
    }

    /**
     * Complete analytics & KPI telemetry for the Admin Dashboard
     * Supports both Consolidated (All Stores) and 1 Selected Store filtering,
     * as well as client-scoped store assignments.
     */
    public function getStats(Request $request)
    {
        // 1. Determine user access rights
        $user = $request->user('sanctum') ?: $request->user();
        $isRestricted = $user && $user->isRestrictedToStores();

        if ($isRestricted) {
            $accessibleStoreIds = $user->getAccessibleStoreIds();
            if (empty($accessibleStoreIds)) {
                $accessibleStoreIds = [Store::where('is_active', true)->value('id') ?: Store::value('id')];
            }
        } else {
            $accessibleStoreIds = Store::pluck('id')->map(fn($id) => (int)$id)->toArray();
        }

        $accessibleStores = Store::whereIn('id', $accessibleStoreIds)->get();

        // 2. Determine target store scope (All Stores vs 1 Specific Store)
        $requestedStoreId = $request->query('store_id');
        $hasMultipleStores = count($accessibleStoreIds) > 1;

        if (!$hasMultipleStores) {
            // Client only has 1 store assigned
            $targetStoreIds = $accessibleStoreIds;
            $selectedStoreId = (string) $accessibleStoreIds[0];
        } elseif (!empty($requestedStoreId) && $requestedStoreId !== 'all') {
            $reqId = (int) $requestedStoreId;
            if (in_array($reqId, $accessibleStoreIds, true)) {
                $targetStoreIds = [$reqId];
                $selectedStoreId = (string) $reqId;
            } else {
                $targetStoreIds = $accessibleStoreIds;
                $selectedStoreId = 'all';
            }
        } else {
            // 'all' stores among accessible
            $targetStoreIds = $accessibleStoreIds;
            $selectedStoreId = 'all';
        }

        $selectedStoreModel = $selectedStoreId !== 'all'
            ? $accessibleStores->firstWhere('id', (int) $selectedStoreId)
            : null;

        // 3. Base order query scoped to target store(s)
        $baseOrderQuery = Order::whereIn('store_id', $targetStoreIds);

        // 4. Calculate Multi-Timeframe Telemetry (Weekly, Monthly, Yearly, All-Time)
        $now = \Carbon\Carbon::now();
        $firstOrder = (clone $baseOrderQuery)->oldest('created_at')->first();
        $firstOrderDate = $firstOrder ? $firstOrder->created_at->format('M d, Y') : $now->format('M d, Y');

        $timeframes = [
            'weekly' => $this->computeTimeframeMetrics(clone $baseOrderQuery, $now->copy()->subDays(7), 'Last 7 Days'),
            'monthly' => $this->computeTimeframeMetrics(clone $baseOrderQuery, $now->copy()->subDays(30), 'Last 30 Days'),
            'yearly' => $this->computeTimeframeMetrics(clone $baseOrderQuery, $now->copy()->subDays(365), 'Last 365 Days'),
            'all' => $this->computeTimeframeMetrics(clone $baseOrderQuery, null, "From Day 1 ({$firstOrderDate})"),
        ];

        // 5. Timeline chart data points for visualization
        $chartData = [
            'weekly' => $this->getWeeklyTimeline(clone $baseOrderQuery),
            'monthly' => $this->getMonthlyTimeline(clone $baseOrderQuery),
            'yearly' => $this->getYearlyTimeline(clone $baseOrderQuery),
            'all' => $this->getAllTimeline(clone $baseOrderQuery),
        ];

        // 6. Multi-Store Telemetry in PKR (scoped to accessible stores)
        $storesBreakdown = Store::whereIn('id', $accessibleStoreIds)
            ->withCount(['products', 'orders'])
            ->get()
            ->map(function ($store) {
                $rev = Order::where('store_id', $store->id)
                    ->where('status', '!=', 'cancelled')
                    ->sum('final_amount');
                $deliveredCount = Order::where('store_id', $store->id)->where('status', 'delivered')->count();
                $cancelledCount = Order::where('store_id', $store->id)->where('status', 'cancelled')->count();

                return [
                    'id' => $store->id,
                    'name' => $store->name,
                    'store_type' => $store->store_type,
                    'currency' => 'PKR',
                    'currency_symbol' => 'Rs. ',
                    'theme_key' => $store->theme_key,
                    'is_active' => (bool) $store->is_active,
                    'products_count' => $store->products_count,
                    'orders_count' => $store->orders_count,
                    'delivered_count' => $deliveredCount,
                    'cancelled_count' => $cancelledCount,
                    'revenue' => (float) $rev,
                ];
            });

        // 7. Recent Orders
        $recentOrders = (clone $baseOrderQuery)
            ->with(['customer', 'store', 'items'])
            ->latest()
            ->take(8)
            ->get();

        // 8. Status distribution across all time
        $ordersByStatus = (clone $baseOrderQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        // 9. Payment methods breakdown
        $paymentBreakdown = (clone $baseOrderQuery)
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(final_amount) as total_amount'))
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        // 10. Top Selling Products
        $topProducts = Product::with('store')
            ->where('status', 'active')
            ->whereIn('store_id', $targetStoreIds)
            ->take(6)
            ->get();

        $selectedStoreName = $selectedStoreModel
            ? $selectedStoreModel->name
            : ($hasMultipleStores ? 'All Stores (Consolidated)' : ($accessibleStores->first()?->name ?? 'Store Overview'));

        return response()->json([
            'currency' => 'PKR',
            'currency_symbol' => 'Rs. ',
            'first_order_date' => $firstOrderDate,
            'is_client_mode' => $isRestricted && !$hasMultipleStores,
            'has_multiple_stores' => $hasMultipleStores,
            'selected_store_id' => $selectedStoreId,
            'selected_store_name' => $selectedStoreName,
            'active_store' => $selectedStoreModel ?: $accessibleStores->first(),
            'accessible_stores' => $accessibleStores->map(function ($st) {
                return [
                    'id' => $st->id,
                    'name' => $st->name,
                    'slug' => $st->slug,
                    'store_type' => $st->store_type,
                    'is_active' => (bool) $st->is_active,
                ];
            }),
            'timeframes' => $timeframes,
            'chart_data' => $chartData,
            'stores_breakdown' => $storesBreakdown,
            'recent_orders' => $recentOrders,
            'orders_by_status' => $ordersByStatus,
            'payment_breakdown' => $paymentBreakdown,
            'top_products' => $topProducts,
            'catalog_total_products' => Product::whereIn('store_id', $targetStoreIds)->count(),
            'total_customers' => Customer::count(),
            'total_subscribers' => NewsletterSubscriber::count(),
        ]);
    }

    /**
     * Compute comprehensive metrics for a given query and start date
     */
    protected function computeTimeframeMetrics($query, $since = null, $label = '')
    {
        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        $allOrders = (clone $query)->get();

        $totalOrders = $allOrders->count();
        $deliveredOrders = $allOrders->where('status', 'delivered');
        $cancelledOrders = $allOrders->where('status', 'cancelled');
        $inTransitOrders = $allOrders->whereIn('status', ['shipped', 'processing']);
        $pendingOrders = $allOrders->where('status', 'pending');

        $activeOrders = $allOrders->where('status', '!=', 'cancelled');
        $totalRevenue = (float) $activeOrders->sum('final_amount');
        $deliveredAmount = (float) $deliveredOrders->sum('final_amount');
        $cancelledAmount = (float) $cancelledOrders->sum('final_amount');
        $inTransitAmount = (float) $inTransitOrders->sum('final_amount');
        $pendingAmount = (float) $pendingOrders->sum('final_amount');

        $activeCount = $activeOrders->count();
        $aov = $activeCount > 0 ? round($totalRevenue / $activeCount) : 0;
        $deliveryRate = $totalOrders > 0 ? round(($deliveredOrders->count() / $totalOrders) * 100, 1) : 0;
        $cancellationRate = $totalOrders > 0 ? round(($cancelledOrders->count() / $totalOrders) * 100, 1) : 0;

        return [
            'label' => $label,
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'delivered_count' => $deliveredOrders->count(),
            'delivered_amount' => $deliveredAmount,
            'cancelled_count' => $cancelledOrders->count(),
            'cancelled_amount' => $cancelledAmount,
            'in_transit_count' => $inTransitOrders->count(),
            'in_transit_amount' => $inTransitAmount,
            'pending_count' => $pendingOrders->count(),
            'pending_amount' => $pendingAmount,
            'aov' => $aov,
            'delivery_rate' => $deliveryRate,
            'cancellation_rate' => $cancellationRate,
        ];
    }

    /**
     * Daily timeline points for last 7 days
     */
    protected function getWeeklyTimeline($query)
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::now()->subDays($i);
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $dayOrders = (clone $query)->whereBetween('created_at', [$dayStart, $dayEnd])->get();

            $days[] = [
                'label' => $date->format('D, M d'),
                'short_label' => $date->format('D'),
                'date' => $date->format('Y-m-d'),
                'revenue' => (float) $dayOrders->where('status', '!=', 'cancelled')->sum('final_amount'),
                'orders' => $dayOrders->count(),
                'delivered' => $dayOrders->where('status', 'delivered')->count(),
                'cancelled' => $dayOrders->where('status', 'cancelled')->count(),
            ];
        }
        return $days;
    }

    /**
     * Grouped timeline points for last 30 days
     */
    protected function getMonthlyTimeline($query)
    {
        $points = [];
        for ($i = 29; $i >= 0; $i -= 3) {
            $start = \Carbon\Carbon::now()->subDays($i)->startOfDay();
            $end = \Carbon\Carbon::now()->subDays(max(0, $i - 2))->endOfDay();

            $rangeOrders = (clone $query)->whereBetween('created_at', [$start, $end])->get();

            $points[] = [
                'label' => $start->format('M d') . ' - ' . $end->format('M d'),
                'short_label' => $start->format('M d'),
                'revenue' => (float) $rangeOrders->where('status', '!=', 'cancelled')->sum('final_amount'),
                'orders' => $rangeOrders->count(),
                'delivered' => $rangeOrders->where('status', 'delivered')->count(),
                'cancelled' => $rangeOrders->where('status', 'cancelled')->count(),
            ];
        }
        return $points;
    }

    /**
     * Monthly timeline points for last 12 months
     */
    protected function getYearlyTimeline($query)
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = \Carbon\Carbon::now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $monthOrders = (clone $query)->whereBetween('created_at', [$start, $end])->get();

            $months[] = [
                'label' => $month->format('M Y'),
                'short_label' => $month->format('M'),
                'revenue' => (float) $monthOrders->where('status', '!=', 'cancelled')->sum('final_amount'),
                'orders' => $monthOrders->count(),
                'delivered' => $monthOrders->where('status', 'delivered')->count(),
                'cancelled' => $monthOrders->where('status', 'cancelled')->count(),
            ];
        }
        return $months;
    }

    /**
     * All time timeline points from inception
     */
    protected function getAllTimeline($query)
    {
        return $this->getYearlyTimeline($query);
    }
}
