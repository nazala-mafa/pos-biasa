<?php

namespace App\Models;

use Database\Factories\PurchaseOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    /** @use HasFactory<PurchaseOrderItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['purchase_order_id', 'product_id', 'quantity', 'received_quantity', 'unit_cost', 'subtotal'];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'received_quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
