<?php

namespace App\Models;

use Database\Factories\StockOpnameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    /** @use HasFactory<StockOpnameFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['team_id', 'user_id', 'number', 'counted_at', 'note'];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'counted_at' => 'date',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<StockOpnameItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }
}
