<?php

namespace App\Models;

use Database\Factories\StockOpnameItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    /** @use HasFactory<StockOpnameItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['stock_opname_id', 'product_id', 'system_quantity', 'counted_quantity', 'difference'];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'system_quantity' => 'decimal:2',
            'counted_quantity' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<StockOpname, $this>
     */
    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
