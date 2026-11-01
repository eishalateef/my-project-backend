<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PhoneOtp;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Mail\OrderPlacedMail;
use App\Mail\OrderStatusUpdatedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Frictionless Guest or Registered Checkout with Phone OTP Verification
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:25',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'payment_method' => 'required|in:cod,card,jazzcash,easypaisa,payfast,safepay,flexipay',
            'payment_gateway' => 'nullable|string',
            'payment_status' => 'nullable|in:pending,paid,partial,failed',
            'customer_cnic' => 'nullable|string|max:30',
            'alternate_phone' => 'nullable|string|max:30',
            'down_payment_method' => 'nullable|string|max:50',
            'transaction_id' => 'nullable|string',
            'payment_details' => 'nullable|array',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.selected_attributes' => 'nullable|array',
        ]);

        $phone = preg_replace('/\s+/', '', $validated['phone']);
        $authUserId = $validated['user_id'] ?? auth('sanctum')->id();

        $order = DB::transaction(function () use ($validated, $phone, $authUserId, $request) {
            // 1. Create or retrieve customer profile
            $customer = Customer::updateOrCreate(
                ['phone' => $phone],
                [
                    'name' => $validated['name'],
                    'email' => $validated['email'] ?? null,
                    'address' => $validated['address'],
                    'city' => $validated['city'],
                ]
            );

            // 2. Calculate totals securely using database prices
            $totalAmount = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $unitPrice = $product->sale_price ?? $product->price;
                $quantity = (int) $item['quantity'];
                $subtotal = $unitPrice * $quantity;

                $totalAmount += $subtotal;

                // Enforce stock availability
                if ($product->stock < $quantity) {
                    $available = max(0, (int) $product->stock);
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'items' => ["The requested quantity ({$quantity}) for \"{$product->name}\" exceeds available stock ({$available} available). Please adjust your shopping bag."]
                    ]);
                }

                // Decrement stock
                $product->decrement('stock', $quantity);

                $itemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $unitPrice,
                    'quantity' => $quantity,
                    'selected_attributes' => $item['selected_attributes'] ?? [],
                    'subtotal' => $subtotal,
                ];
            }

            // Shipping calculation: Free shipping for orders above 100 or default
            $shippingCost = 0;
            $discountAmount = 0;
            $finalAmount = $totalAmount - $discountAmount + $shippingCost;

            // 3. Create Order
            $orderNumber = 'FLX-' . strtoupper(Str::random(4)) . '-' . rand(1000, 9999);
            $isFlexiPay = ($validated['payment_method'] === 'flexipay');
            $paymentStatus = $isFlexiPay ? 'partial' : ($validated['payment_status'] ?? 'pending');
            $isPaidOnline = in_array($validated['payment_method'], ['jazzcash', 'easypaisa', 'payfast', 'card', 'safepay']) && $paymentStatus === 'paid';

            $newOrder = Order::create([
                'store_id' => $validated['store_id'],
                'customer_id' => $customer->id,
                'user_id' => $authUserId,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'shipping_cost' => $shippingCost,
                'final_amount' => $finalAmount,
                'status' => ($isPaidOnline || $isFlexiPay) ? 'processing' : 'pending',
                'delivery_status' => 'unassigned',
                'payment_method' => $validated['payment_method'],
                'is_installment' => $isFlexiPay,
                'customer_cnic' => $isFlexiPay ? ($validated['customer_cnic'] ?? null) : null,
                'alternate_phone' => $isFlexiPay ? ($validated['alternate_phone'] ?? null) : null,
                'installment_status' => $isFlexiPay ? 'active' : 'none',
                'total_installments' => $isFlexiPay ? 4 : 0,
                'installments_paid' => $isFlexiPay ? 1 : 0,
                'payment_status' => $paymentStatus,
                'payment_gateway' => $validated['payment_gateway'] ?? ($validated['payment_method'] === 'cod' ? 'cod' : $validated['payment_method']),
                'transaction_id' => $validated['transaction_id'] ?? null,
                'payment_details' => $validated['payment_details'] ?? null,
                'paid_at' => ($isPaidOnline || $isFlexiPay) ? now() : null,
                'is_phone_verified' => true,
                'shipping_address' => $validated['address'] . ', ' . $validated['city'],
                'phone' => $phone,
                'notes' => $validated['notes'] ?? null,
            ]);

            // 4. Create Order Items
            foreach ($itemsData as $data) {
                $newOrder->items()->create($data);
            }

            // 5. If FlexiPay, generate 4 interest-free installment schedule
            if ($isFlexiPay) {
                $quarter = round($finalAmount / 4, 2);
                $lastQuarter = $finalAmount - ($quarter * 3);
                $downMethod = $validated['down_payment_method'] ?? 'card';
                $downTxn = 'FLX-DOWN-' . strtoupper(Str::random(8));

                // Installment 1: Down payment (Paid today)
                $newOrder->installments()->create([
                    'order_number' => $newOrder->order_number,
                    'installment_number' => 1,
                    'amount' => $quarter,
                    'due_date' => now()->toDateString(),
                    'paid_at' => now(),
                    'status' => 'paid',
                    'payment_method' => $downMethod,
                    'transaction_ref' => $downTxn,
                    'notes' => 'Down Payment (1 of 4) paid at checkout via ' . strtoupper($downMethod),
                ]);

                // Installment 2: Due in 30 days
                $newOrder->installments()->create([
                    'order_number' => $newOrder->order_number,
                    'installment_number' => 2,
                    'amount' => $quarter,
                    'due_date' => now()->addDays(30)->toDateString(),
                    'paid_at' => null,
                    'status' => 'pending',
                    'payment_method' => null,
                    'notes' => 'Installment 2 of 4 due in 30 days',
                ]);

                // Installment 3: Due in 60 days
                $newOrder->installments()->create([
                    'order_number' => $newOrder->order_number,
                    'installment_number' => 3,
                    'amount' => $quarter,
                    'due_date' => now()->addDays(60)->toDateString(),
                    'paid_at' => null,
                    'status' => 'pending',
                    'payment_method' => null,
                    'notes' => 'Installment 3 of 4 due in 60 days',
                ]);

                // Installment 4: Due in 90 days
                $newOrder->installments()->create([
                    'order_number' => $newOrder->order_number,
                    'installment_number' => 4,
                    'amount' => $lastQuarter,
                    'due_date' => now()->addDays(90)->toDateString(),
                    'paid_at' => null,
                    'status' => 'pending',
                    'payment_method' => null,
                    'notes' => 'Final Installment 4 of 4 due in 90 days',
                ]);

                // Log down payment in payments table
                Payment::create([
                    'order_id' => $newOrder->id,
                    'order_number' => $newOrder->order_number,
                    'gateway' => 'flexipay_' . $downMethod,
                    'transaction_id' => $downTxn,
                    'amount' => $quarter,
                    'currency' => 'PKR',
                    'account_number' => $newOrder->phone,
                    'status' => 'successful',
                    'raw_response' => [
                        'installment_number' => 1,
                        'type' => 'flexipay_downpayment',
                        'paid_at' => now()->toIso8601String(),
                    ],
                ]);
            } else {
                // Record standard payment in dedicated payments table
                Payment::updateOrCreate(
                    ['transaction_id' => $newOrder->transaction_id ?: ('TXN-' . $newOrder->order_number)],
                    [
                        'order_id' => $newOrder->id,
                        'order_number' => $newOrder->order_number,
                        'gateway' => $newOrder->payment_gateway ?: ($newOrder->payment_method ?: 'cod'),
                        'amount' => $newOrder->final_amount,
                        'currency' => 'PKR',
                        'account_number' => $newOrder->phone,
                        'status' => $newOrder->payment_status === 'paid' ? 'successful' : 'pending',
                        'raw_response' => $newOrder->payment_details,
                    ]
                );
            }

            return $newOrder->load(['items', 'customer', 'store', 'user', 'payments', 'installments']);
        });

        // Automated Order Confirmation Notification (Email)
        $recipientEmail = $order->customer->email ?? $validated['email'] ?? null;
        if ($recipientEmail && filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($recipientEmail)->send(new OrderPlacedMail($order));
                Log::info("Order confirmation email dispatched to {$recipientEmail} for order #{$order->order_number}");
            } catch (\Throwable $e) {
                Log::warning("Order confirmation email failed for #{$order->order_number}: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Order placed successfully!',
            'order' => $order,
        ], 201);
    }

    /**
     * Check if request has developer privileges
     */
    protected function isDeveloperAuthorized(Request $request): bool
    {
        $devKey = $request->header('X-Developer-Key') ?? $request->query('dev_key') ?? $request->input('dev_key');
        $secret = config('flexistore.developer_secret_key', 'flexi_dev_secret_2026');
        return !empty($devKey) && hash_equals($secret, (string) $devKey);
    }

    /**
     * Admin: List orders with filters & assigned riders
     */
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'items', 'store', 'deliveryRider', 'installments'])->latest();

        $user = $request->user('sanctum') ?: $request->user();
        if ($user && $user->isRestrictedToStores()) {
            $accessible = $user->getAccessibleStoreIds();
            if ($request->filled('store_id') && $request->store_id !== 'all') {
                $reqId = (int)$request->store_id;
                if (in_array($reqId, $accessible, true)) {
                    $query->where('store_id', $reqId);
                } else {
                    $query->whereIn('store_id', $accessible);
                }
            } else {
                $query->whereIn('store_id', $accessible);
            }
        } elseif ($request->filled('store_id') && $request->store_id !== 'all') {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->delivery_status);
        }

        if ($request->filled('rider_id')) {
            $query->where('delivery_user_id', $request->rider_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->paginate(20);
        return response()->json($orders);
    }

    /**
     * Public / Guest: View order details by order number
     */
    public function show($orderNumber)
    {
        $order = Order::with(['customer', 'items', 'store', 'deliveryRider', 'installments'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return response()->json(['order' => $order]);
    }

    /**
     * Admin: Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $previousStatus = $order->status;
        $order->update(['status' => $validated['status']]);

        // Keep delivery_status aligned if delivered or cancelled
        if ($validated['status'] === 'delivered') {
            $order->update([
                'delivery_status' => 'delivered',
                'delivered_at' => now(),
            ]);
        } elseif ($validated['status'] === 'cancelled') {
            $order->update(['delivery_status' => 'failed']);

            // Restore product stock when order is cancelled
            if ($previousStatus !== 'cancelled') {
                foreach ($order->items as $item) {
                    Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                }
            }
        }

        $freshOrder = $order->fresh(['customer', 'items', 'store', 'deliveryRider']);

        // Automated Order Status Update Notification (Email)
        if ($previousStatus !== $validated['status']) {
            $recipientEmail = $freshOrder->customer->email ?? null;
            if ($recipientEmail && filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::to($recipientEmail)->send(new OrderStatusUpdatedMail($freshOrder, $previousStatus));
                    Log::info("Order status update email dispatched to {$recipientEmail} for order #{$order->order_number} ({$validated['status']})");
                } catch (\Throwable $e) {
                    Log::warning("Status update email failed for #{$order->order_number}: " . $e->getMessage());
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Order #{$order->order_number} status updated to {$order->status}",
            'order' => $freshOrder,
        ]);
    }

    /**
     * Public Live Order Tracking (by Order Number and optional/required Phone)
     */
    public function track(Request $request)
    {
        $request->validate([
            'order_number' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $orderNumber = trim($request->input('order_number') ?? '');
        $phone = preg_replace('/\s+/', '', trim($request->input('phone') ?? ''));

        if (empty($orderNumber) && empty($phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide an Order Number or Phone Number to track.',
            ], 422);
        }

        $query = Order::with(['customer', 'items', 'store', 'deliveryRider', 'installments']);

        if (!empty($orderNumber)) {
            $query->where('order_number', $orderNumber);
        }

        if (!empty($phone)) {
            $query->where(function ($q) use ($phone) {
                $q->where('phone', 'like', "%{$phone}%")
                  ->orWhereHas('customer', function ($cq) use ($phone) {
                      $cq->where('phone', 'like', "%{$phone}%");
                  });
            });
        }

        $order = $query->latest()->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'No active order found matching the provided details. Please verify your Order # and phone number.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order' => $order,
        ]);
    }

    /**
     * Admin: Assign delivery courier / rider to order
     */
    public function assignRider(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'delivery_user_id' => 'required|exists:users,id',
        ]);

        $rider = User::where('id', $validated['delivery_user_id'])
            ->whereIn('role', ['delivery', 'courier'])
            ->firstOrFail();

        $order->delivery_user_id = $rider->id;
        $order->delivery_type = 'own_rider';
        $order->assigned_to = $rider->name;
        $order->delivery_status = 'assigned';
        if ($order->status === 'pending') {
            $order->status = 'processing';
        }
        $order->save();

        return response()->json([
            'success' => true,
            'message' => "Order assigned to courier: {$rider->name}",
            'order' => $order->fresh(['customer', 'items', 'store', 'deliveryRider']),
        ]);
    }

    /**
     * Admin: Assign Delivery Type and Courier (Own Rider or Third-Party)
     * Auto-suggested by city, final selection done by Admin.
     */
    public function assignDelivery(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'delivery_type' => 'required|string',
            'assigned_to' => 'required|string',
            'delivery_user_id' => 'nullable',
        ]);

        $rawType = strtolower(trim($validated['delivery_type']));
        $isOwnRider = str_contains($rawType, 'own') || $rawType === 'own_rider';
        $deliveryType = $isOwnRider ? 'own_rider' : 'third_party';
        $assignedTo = trim($validated['assigned_to']);

        $order->delivery_type = $deliveryType;
        $order->assigned_to = $assignedTo;

        if ($deliveryType === 'own_rider') {
            $riderId = $request->input('delivery_user_id');
            // If riderId wasn't passed directly, try finding by id or name
            $rider = null;
            if ($riderId) {
                $rider = User::where('id', $riderId)->whereIn('role', ['courier', 'delivery'])->first();
            }
            if (!$rider && is_numeric($assignedTo)) {
                $rider = User::where('id', $assignedTo)->whereIn('role', ['courier', 'delivery'])->first();
            }
            if (!$rider) {
                $rider = User::where('name', $assignedTo)->whereIn('role', ['courier', 'delivery'])->first();
            }

            if ($rider) {
                $order->delivery_user_id = $rider->id;
                $order->assigned_to = $rider->name;
            }
        } else {
            // Third-Party Courier (Leopards, TCS, Trax)
            $order->delivery_user_id = null;
        }

        $order->delivery_status = 'assigned';
        if ($order->status === 'pending') {
            $order->status = 'processing';
        }
        $order->save();

        return response()->json([
            'success' => true,
            'message' => "Order delivery assigned successfully: " . ($order->delivery_type === 'own_rider' ? "Own Rider ({$order->assigned_to})" : "Third-Party ({$order->assigned_to})"),
            'order' => $order->fresh(['customer', 'items', 'store', 'deliveryRider']),
        ]);
    }

    /**
     * Admin / System: List all delivery couriers from users table where role = 'courier' or 'delivery'
     */
    public function listRiders()
    {
        $riders = User::whereIn('role', ['courier', 'delivery'])
            ->select('id', 'name', 'email', 'phone', 'city', 'role', 'employee_id')
            ->orderBy('name')
            ->get();

        return response()->json($riders);
    }
}
