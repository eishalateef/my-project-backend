<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'email',
        'status',
        'source',
        'ip_address',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
