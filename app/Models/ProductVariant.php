<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'color_name',
        'color_code',
        'image_url',
        'price',
        'stock_quantity',
        'sku',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the product that owns the variant
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get display price (variant price or fall back to product price)
     */
    public function getDisplayPriceAttribute()
    {
        return $this->price ?? $this->product->price;
    }

    /**
     * Check if variant is in stock
     */
    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }
}
