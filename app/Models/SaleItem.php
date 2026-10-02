<?php

namespace App\Models;

use Database\Factories\SaleItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    /** @use HasFactory<SaleItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['sale_id', 'product_id', 'sku', 'name', 'quantity', 'unit_price', 'tax_rate', 'tax_amount', 'subtotal'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
