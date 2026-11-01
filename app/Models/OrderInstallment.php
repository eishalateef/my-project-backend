<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderInstallment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_number',
        'installment_number',
        'amount',
        'due_date',
        'paid_at',
        'status',
        'payment_method',
        'transaction_ref',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'installment_number' => 'integer',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
