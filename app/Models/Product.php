<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Category;     // <--- ADD THIS
use App\Models\ProductImage; // <--- ADD THIS

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug', // <--- Ensure this is here
        'description',
        'price',
        'stock',
        'is_active'
    ];

    // Ensure 'category' relationship is singular
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Ensure 'images' relationship is plural
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }
}
