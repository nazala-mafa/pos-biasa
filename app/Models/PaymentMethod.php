<?php

namespace App\Models;

use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory;

    /**
     * Methods every new outlet starts with.
     *
     * @var list<array{name: string, is_cash: bool}>
     */
    public const DEFAULTS = [
        ['name' => 'Cash', 'is_cash' => true],
        ['name' => 'Bank transfer', 'is_cash' => false],
        ['name' => 'QRIS', 'is_cash' => false],
    ];

    /**
     * @var list<string>
     */
    protected $fillable = ['team_id', 'name', 'is_cash', 'is_active'];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'is_cash' => 'boolean',
            'is_active' => 'boolean',
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
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
