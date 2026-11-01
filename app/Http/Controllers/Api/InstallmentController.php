<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderInstallment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InstallmentController extends Controller
{
    /**
     * Get Installment Ledger & Breakdown for a specific order (Customer / Public / Admin)
     */
    public function getInstallments($orderIdentifier)
    {
        $order = Order::with(['customer', 'items', 'installments'])
            ->where('order_number', $orderIdentifier)
            ->orWhere('id', is_numeric($orderIdentifier) ? $orderIdentifier : 0)
            ->firstOrFail();

        // Update overdue statuses dynamically if past due date
        $now = now()->startOfDay();
        $hasOverdue = false;

        foreach ($order->installments as $inst) {
            if ($inst->status === 'pending' && $inst->due_date && $inst->due_date < $now) {
                $inst->update(['status' => 'overdue']);
                $hasOverdue = true;
            }
        }

        if ($hasOverdue && $order->installment_status === 'active') {
            $order->update(['installment_status' => 'overdue']);
        }

        $freshInstallments = $order->installments()->orderBy('installment_number', 'asc')->get();

        $totalAmount = (float) $order->final_amount;
        $paidAmount = (float) $freshInstallments->where('status', 'paid')->sum('amount');
        $remainingAmount = max(0, $totalAmount - $paidAmount);
        $nextPending = $freshInstallments->firstWhere('status', '!=', 'paid');

        return response()->json([
            'success' => true,
            'order' => $order,
            'installments' => $freshInstallments,
            'summary' => [
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'percent_paid' => $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100) : 0,
                'total_installments' => $order->total_installments ?? 4,
                'installments_paid' => $freshInstallments->where('status', 'paid')->count(),
                'installments_remaining' => $freshInstallments->where('status', '!=', 'paid')->count(),
                'next_due_date' => $nextPending ? $nextPending->due_date : null,
                'next_installment_id' => $nextPending ? $nextPending->id : null,
                'next_installment_amount' => $nextPending ? (float) $nextPending->amount : 0,
                'is_overdue' => $freshInstallments->where('status', 'overdue')->isNotEmpty(),
                'installment_status' => $order->installment_status,
            ],
        ]);
    }

    /**
     * Customer Pays an Installment Online (JazzCash, Easypaisa, or Card)
     */
    public function payInstallment(Request $request, $installmentId)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:jazzcash,easypaisa,card,payfast,safepay',
            'account_number' => 'nullable|string|max:30',
            'transaction_ref' => 'nullable|string|max:100',
        ]);

        $installment = OrderInstallment::with('order.customer')->findOrFail($installmentId);

        if ($installment->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => "Installment #{$installment->installment_number} has already been paid.",
            ], 422);
        }

        $order = $installment->order;
        $txnRef = $validated['transaction_ref'] ?? ('FLX-INST-' . strtoupper(Str::random(8)));

        DB::transaction(function () use ($installment, $order, $validated, $txnRef) {
            // 1. Mark installment as paid
            $installment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $validated['payment_method'],
                'transaction_ref' => $txnRef,
                'notes' => 'Customer online payment via ' . strtoupper($validated['payment_method']),
            ]);

            // 2. Log in payments ledger
            Payment::create([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'gateway' => $validated['payment_method'],
                'transaction_id' => $txnRef,
                'amount' => $installment->amount,
                'currency' => 'PKR',
                'account_number' => $validated['account_number'] ?? $order->phone,
                'status' => 'successful',
                'raw_response' => [
                    'installment_number' => $installment->installment_number,
                    'paid_at' => now()->toIso8601String(),
                ],
            ]);

            // 3. Update order statistics
            $paidCount = OrderInstallment::where('order_id', $order->id)->where('status', 'paid')->count();
            $totalCount = $order->total_installments ?? 4;

            $newInstallmentStatus = ($paidCount >= $totalCount) ? 'completed' : 'active';
            $newPaymentStatus = ($paidCount >= $totalCount) ? 'paid' : 'partial';

            $order->update([
                'installments_paid' => $paidCount,
                'installment_status' => $newInstallmentStatus,
                'payment_status' => $newPaymentStatus,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Installment #{$installment->installment_number} of Rs. " . number_format($installment->amount) . " paid successfully!",
            'installment' => $installment->fresh(),
            'order' => $order->fresh(['installments', 'customer']),
        ]);
    }

    /**
     * Admin: Record Manual / Offline Payment (Cash, Bank Transfer, or In-Store)
     */
    public function adminRecordPayment(Request $request, $installmentId)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:cash,bank_transfer,jazzcash,easypaisa,card,manual',
            'transaction_ref' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        $installment = OrderInstallment::with('order')->findOrFail($installmentId);

        if ($installment->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => "Installment #{$installment->installment_number} is already marked as paid.",
            ], 422);
        }

        $order = $installment->order;
        $txnRef = $validated['transaction_ref'] ?? ('MANUAL-' . strtoupper(Str::random(6)));
        $notes = $validated['notes'] ?? ('Admin verified offline payment (' . ucfirst($validated['payment_method']) . ')');

        DB::transaction(function () use ($installment, $order, $validated, $txnRef, $notes) {
            $installment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $validated['payment_method'],
                'transaction_ref' => $txnRef,
                'notes' => $notes,
            ]);

            $paidCount = OrderInstallment::where('order_id', $order->id)->where('status', 'paid')->count();
            $totalCount = $order->total_installments ?? 4;

            $newInstallmentStatus = ($paidCount >= $totalCount) ? 'completed' : 'active';
            $newPaymentStatus = ($paidCount >= $totalCount) ? 'paid' : 'partial';

            $order->update([
                'installments_paid' => $paidCount,
                'installment_status' => $newInstallmentStatus,
                'payment_status' => $newPaymentStatus,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Installment #{$installment->installment_number} marked as Paid by Admin.",
            'installment' => $installment->fresh(),
            'order' => $order->fresh(['installments', 'customer']),
        ]);
    }

    /**
     * Admin: Overview KPI Dashboard & List of all FlexiPay Installment Plans
     */
    public function adminDashboard(Request $request)
    {
        $query = Order::with(['customer', 'installments'])
            ->where('is_installment', true)
            ->latest();

        if ($request->filled('status')) {
            $query->where('installment_status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_cnic', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->paginate(20);

        // Aggregate Metrics
        $totalInstallmentOrders = Order::where('is_installment', true)->count();
        $totalFinanced = (float) Order::where('is_installment', true)->sum('final_amount');
        $totalCollected = (float) OrderInstallment::where('status', 'paid')->sum('amount');
        $totalOutstanding = max(0, $totalFinanced - $totalCollected);
        $overdueCount = OrderInstallment::where('status', 'overdue')->count();
        $activePlans = Order::where('is_installment', true)->where('installment_status', 'active')->count();
        $completedPlans = Order::where('is_installment', true)->where('installment_status', 'completed')->count();

        return response()->json([
            'success' => true,
            'kpis' => [
                'total_orders' => $totalInstallmentOrders,
                'total_financed' => $totalFinanced,
                'total_collected' => $totalCollected,
                'total_outstanding' => $totalOutstanding,
                'overdue_count' => $overdueCount,
                'active_plans' => $activePlans,
                'completed_plans' => $completedPlans,
            ],
            'orders' => $orders,
        ]);
    }
}
