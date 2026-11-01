<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'image',
        'icon',
        'parent_id',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Recursively fetch all category IDs under this branch (including self)
     */
    public function getDescendantsAndSelfIds(): array
    {
        $ids = [$this->id];
        $children = Category::where('parent_id', $this->id)->get();
        foreach ($children as $child) {
            $ids = array_merge($ids, $child->getDescendantsAndSelfIds());
        }
        return array_values(array_unique($ids));
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
