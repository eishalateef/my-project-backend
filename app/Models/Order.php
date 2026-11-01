<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'customer_id',
        'user_id',
        'delivery_user_id',
        'order_number',
        'total_amount',
        'discount_amount',
        'shipping_cost',
        'final_amount',
        'status',
        'delivery_status',
        'delivery_type',
        'assigned_to',
        'payment_method',
        'is_installment',
        'customer_cnic',
        'alternate_phone',
        'installment_status',
        'total_installments',
        'installments_paid',
        'payment_status',
        'payment_gateway',
        'transaction_id',
        'payment_details',
        'paid_at',
        'is_phone_verified',
        'shipping_address',
        'phone',
        'notes',
        'delivery_notes',
        'delivered_at',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'discount_amount' => 'float',
        'shipping_cost' => 'float',
        'final_amount' => 'float',
        'payment_details' => 'array',
        'paid_at' => 'datetime',
        'is_phone_verified' => 'boolean',
        'is_installment' => 'boolean',
        'total_installments' => 'integer',
        'installments_paid' => 'integer',
        'delivered_at' => 'datetime',
    ];

    public function installments(): HasMany
    {
        return $this->hasMany(OrderInstallment::class, 'order_id')->orderBy('installment_number', 'asc');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function deliveryRider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
