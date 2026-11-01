<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterController extends Controller
{
    /**
     * Public endpoint to subscribe an email to Khaam Poshak updates.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'source' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('email') ?: 'Please enter a valid email address.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $normalizedEmail = strtolower(trim($request->input('email')));
        $activeStore = Store::where('is_active', true)->first() ?: Store::first();
        $storeId = $activeStore ? $activeStore->id : null;

        $existing = NewsletterSubscriber::where('email', $normalizedEmail)->first();

        if ($existing) {
            if ($existing->status === 'subscribed') {
                return response()->json([
                    'success' => true,
                    'status' => 'already_subscribed',
                    'message' => 'You are already subscribed to Khaam Poshak updates!',
                    'subscriber' => $existing,
                ], 200);
            }

            // Reactivate unsubscribed email
            $existing->update([
                'status' => 'subscribed',
                'store_id' => $storeId,
            ]);

            return response()->json([
                'success' => true,
                'status' => 'reactivated',
                'message' => 'Welcome back! Your subscription to Khaam Poshak has been reactivated.',
                'subscriber' => $existing,
            ], 200);
        }

        $subscriber = NewsletterSubscriber::create([
            'store_id' => $storeId,
            'email' => $normalizedEmail,
            'status' => 'subscribed',
            'source' => $request->input('source', 'website_footer'),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'status' => 'subscribed',
            'message' => "Thank you for subscribing! You'll be the first to know about new handcrafted drops and exclusive offers.",
            'subscriber' => $subscriber,
        ], 201);
    }

    /**
     * Admin endpoint: list all subscribers with search, status filtering, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = NewsletterSubscriber::with('store')->latest();

        if ($search = $request->query('search')) {
            $query->where('email', 'like', "%{$search}%");
        }

        if ($status = $request->query('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        $subscribers = $query->paginate($request->query('per_page', 50));
        $totalSubscribed = NewsletterSubscriber::where('status', 'subscribed')->count();
        $totalUnsubscribed = NewsletterSubscriber::where('status', 'unsubscribed')->count();

        return response()->json([
            'success' => true,
            'data' => $subscribers->items(),
            'current_page' => $subscribers->currentPage(),
            'last_page' => $subscribers->lastPage(),
            'per_page' => $subscribers->perPage(),
            'total' => $subscribers->total(),
            'counts' => [
                'total' => NewsletterSubscriber::count(),
                'subscribed' => $totalSubscribed,
                'unsubscribed' => $totalUnsubscribed,
            ],
        ]);
    }

    /**
     * Admin endpoint: remove a subscriber.
     */
    public function destroy($id): JsonResponse
    {
        $subscriber = NewsletterSubscriber::find($id);

        if (!$subscriber) {
            return response()->json([
                'success' => false,
                'message' => 'Subscriber not found.',
            ], 404);
        }

        $subscriber->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subscriber has been removed successfully.',
        ]);
    }

    /**
     * Admin endpoint: toggle status between subscribed and unsubscribed.
     */
    public function toggleStatus($id): JsonResponse
    {
        $subscriber = NewsletterSubscriber::find($id);

        if (!$subscriber) {
            return response()->json([
                'success' => false,
                'message' => 'Subscriber not found.',
            ], 404);
        }

        $newStatus = $subscriber->status === 'subscribed' ? 'unsubscribed' : 'subscribed';
        $subscriber->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => "Subscriber status updated to {$newStatus}.",
            'subscriber' => $subscriber,
        ]);
    }

    /**
     * Admin endpoint: export all subscribers as a CSV file.
     */
    public function exportCsv(): StreamedResponse
    {
        $fileName = 'khaam_poshak_subscribers_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['ID', 'Email', 'Status', 'Source', 'Subscribed At'];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            NewsletterSubscriber::chunk(200, function ($subscribers) use ($file) {
                foreach ($subscribers as $sub) {
                    fputcsv($file, [
                        $sub->id,
                        $sub->email,
                        ucfirst($sub->status),
                        $sub->source ?: 'website_footer',
                        $sub->created_at ? $sub->created_at->format('Y-m-d H:i:s') : 'N/A',
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
