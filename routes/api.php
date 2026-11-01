<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\InstallmentController;
use App\Http\Controllers\Api\NewsletterController;

/*
|--------------------------------------------------------------------------
| FlexiStore Pro API Routes
|--------------------------------------------------------------------------
*/

// Public Storefront Endpoints
Route::prefix('store')->group(function () {
    Route::get('/active', [StoreController::class, 'getActiveStore']);
    Route::get('/all', [StoreController::class, 'listStores']);
});

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{idOrSlug}', [ProductController::class, 'show']);
});

Route::get('/categories', [CategoryController::class, 'index']);

// Newsletter VIP Subscription
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->middleware('throttle:15,1');

// Phone OTP Guest Verification (Rate Limited against SMS spam & brute-force)
Route::prefix('otp')->group(function () {
    Route::post('/send', [OtpController::class, 'sendOtp'])->middleware('throttle:10,1');
    Route::post('/verify', [OtpController::class, 'verifyOtp'])->middleware('throttle:15,1');
});

// Checkout & Order Tracking (Protected against double submissions and scraping)
Route::post('/checkout', [OrderController::class, 'checkout'])->middleware('throttle:20,1');
Route::get('/orders/lookup/{orderNumber}', [OrderController::class, 'show']);
Route::match(['get', 'post'], '/orders/track', [OrderController::class, 'track'])->middleware('throttle:30,1');

// FlexiPay 4-Installment Engine Routes
Route::prefix('installments')->group(function () {
    Route::get('/lookup/{orderIdentifier}', [InstallmentController::class, 'getInstallments']);
    Route::post('/{id}/pay', [InstallmentController::class, 'payInstallment'])->middleware('throttle:15,1');
});

// Online Payment Gateway Routes (JazzCash, Easypaisa, PayFast)
Route::prefix('payment')->group(function () {
    Route::post('/initiate', [PaymentController::class, 'initiate'])->middleware('throttle:15,1');
    Route::post('/verify', [PaymentController::class, 'verify'])->middleware('throttle:20,1');
    Route::match(['get', 'post'], '/callback/{gateway}', [PaymentController::class, 'callback']);
    Route::match(['get', 'post'], '/webhook/{gateway}', [PaymentController::class, 'webhook']);
});

// Authentication (Protected against credential stuffing & brute-force)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
});

// Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Shopper / Customer Portal
    Route::prefix('customer')->group(function () {
        Route::get('/orders', [CustomerController::class, 'orders']);
        Route::post('/orders/{id}/cancel', [CustomerController::class, 'cancelOrder']);
        Route::put('/profile', [CustomerController::class, 'updateProfile']);
    });

    // Delivery Courier / Rider Portal
    Route::prefix('delivery')->group(function () {
        Route::get('/orders', [DeliveryController::class, 'index']);
        Route::patch('/orders/{id}/status', [DeliveryController::class, 'updateStatus']);
        Route::get('/stats', [DeliveryController::class, 'stats']);
    });
});

// Fallback open access to delivery and customer routes for instant preview (throttled against flooding)
Route::prefix('delivery-public')->middleware('throttle:60,1')->group(function () {
    Route::get('/orders', [DeliveryController::class, 'index']);
    Route::patch('/orders/{id}/status', [DeliveryController::class, 'updateStatus']);
    Route::get('/stats', [DeliveryController::class, 'stats']);
});

Route::prefix('customer-public')->middleware('throttle:60,1')->group(function () {
    Route::get('/orders', [CustomerController::class, 'orders']);
    Route::post('/orders/{id}/cancel', [CustomerController::class, 'cancelOrder']);
    Route::put('/profile', [CustomerController::class, 'updateProfile']);
});

// Admin Controls & Business Intelligence (throttled against brute-force)
Route::prefix('admin')->middleware('throttle:60,1')->group(function () {
    // Developer Master Unlock
    Route::post('/developer/unlock', [StoreController::class, 'verifyDeveloperKey']);

    // 1-Click Store Switcher Logic
    Route::post('/store/switch', [StoreController::class, 'switchStore']);
    Route::put('/store/{id}', [StoreController::class, 'updateStoreSettings']);

    // Admin Dashboard KPIs & Charts
    Route::get('/dashboard/stats', [AdminDashboardController::class, 'getStats']);

    // Order Management & Rider Assignment
    Route::get('/orders', [OrderController::class, 'index']);
    Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::get('/riders', [OrderController::class, 'listRiders']);
    Route::patch('/orders/{id}/assign-rider', [OrderController::class, 'assignRider']);
    Route::patch('/orders/{id}/assign-delivery', [OrderController::class, 'assignDelivery']);

    // Admin FlexiPay Installment Ledger & Operations
    Route::get('/installments/dashboard', [InstallmentController::class, 'adminDashboard']);
    Route::post('/installments/{id}/record-payment', [InstallmentController::class, 'adminRecordPayment']);

    // Staff & Employee ID Management
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::put('/employees/{id}', [EmployeeController::class, 'update']);
    Route::patch('/employees/{id}/toggle-status', [EmployeeController::class, 'toggleStatus']);
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);

    // Product CRUD with Dynamic Attributes & Multi-Image PC Upload
    Route::post('/products/upload-images', [ProductController::class, 'uploadImages']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Category Management
    Route::post('/categories/upload-image', [CategoryController::class, 'uploadImage']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // Newsletter Subscribers Management
    Route::get('/newsletter/subscribers', [NewsletterController::class, 'index']);
    Route::delete('/newsletter/subscribers/{id}', [NewsletterController::class, 'destroy']);
    Route::patch('/newsletter/subscribers/{id}/toggle-status', [NewsletterController::class, 'toggleStatus']);
    Route::get('/newsletter/export-csv', [NewsletterController::class, 'exportCsv']);
});
