<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'employee_id',
        'assigned_store_ids',
        'is_active',
        'phone',
        'address',
        'city',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'assigned_store_ids' => 'array',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDelivery(): bool
    {
        return $this->role === 'delivery' || $this->role === 'courier';
    }

    public function isCourier(): bool
    {
        return $this->isDelivery();
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer' || empty($this->role);
    }

    /**
     * Check if user is restricted to a specific subset of stores (client or branch admin)
     */
    public function isRestrictedToStores(): bool
    {
        if (empty($this->assigned_store_ids)) {
            return false;
        }
        return !in_array('all', (array) $this->assigned_store_ids);
    }

    /**
     * Check if user has permission to view or manage a given store ID
     */
    public function hasAccessToStore($storeId): bool
    {
        if (!$this->isRestrictedToStores()) {
            return true;
        }
        $assigned = array_map('intval', (array) $this->assigned_store_ids);
        return in_array((int) $storeId, $assigned, true);
    }

    /**
     * Get array of store IDs accessible by this user
     */
    public function getAccessibleStoreIds(): array
    {
        if (!$this->isRestrictedToStores()) {
            return \App\Models\Store::pluck('id')->toArray();
        }
        return array_map('intval', (array) $this->assigned_store_ids);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id')->latest();
    }

    public function assignedDeliveries(): HasMany
    {
        return $this->hasMany(Order::class, 'delivery_user_id')->latest();
    }
}
