<?php

namespace Adichan\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariation extends Model
{
    use HasFactory;

    protected $table = 'product_variations';

    protected $guarded = ['id'];

    protected $casts = [
        'attributes' => 'array',
        'price_override' => 'float',
        'base_price' => 'float',

    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
