<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['team_id', 'customer_id', 'user_id', 'invoice_number', 'payment_method', 'subtotal', 'discount', 'tax_total', 'total', 'paid', 'change', 'note'];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'paid' => 'decimal:2',
            'change' => 'decimal:2',
        ];
    }

    /**
     * The outlet this record belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * The cashier who recorded the sale.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<SaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
