<?php

namespace App\Models;

use Database\Factories\OptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Flexible key-value store. The scope is decided by the nullable owners:
 * - team_id null, user_id null: application-wide setting
 * - team_id set,  user_id null: outlet setting
 * - team_id null, user_id set:  user personalization
 * - team_id set,  user_id set:  user personalization within an outlet
 *
 * @property int $id
 * @property int|null $team_id
 * @property int|null $user_id
 * @property string $key
 * @property string|null $value
 * @property bool $is_json
 */
class Option extends Model
{
    /** @use HasFactory<OptionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['team_id', 'user_id', 'key', 'value', 'is_json'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_json' => 'boolean',
        ];
    }

    /**
     * The outlet this option belongs to, if any.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * The user this option belongs to, if any.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Restrict the query to an exact scope; null means "not owned by".
     *
     * @param  Builder<Option>  $query
     */
    public function scopeForScope(Builder $query, ?int $teamId = null, ?int $userId = null): void
    {
        $query->where('team_id', $teamId)->where('user_id', $userId);
    }

    /**
     * Read the decoded value of an option.
     */
    public static function get(string $key, mixed $default = null, ?int $teamId = null, ?int $userId = null): mixed
    {
        $option = static::forScope($teamId, $userId)->where('key', $key)->first();

        return $option === null ? $default : $option->decodedValue();
    }

    /**
     * Store a value; arrays and other non-scalars are saved as JSON.
     */
    public static function put(string $key, mixed $value, ?int $teamId = null, ?int $userId = null): self
    {
        $isJson = ! is_null($value) && ! is_scalar($value);

        return static::updateOrCreate(
            ['team_id' => $teamId, 'user_id' => $userId, 'key' => $key],
            [
                'value' => $isJson ? json_encode($value) : (is_bool($value) ? (string) (int) $value : $value),
                'is_json' => $isJson,
            ],
        );
    }

    /**
     * Remove an option from the given scope.
     */
    public static function forget(string $key, ?int $teamId = null, ?int $userId = null): void
    {
        static::forScope($teamId, $userId)->where('key', $key)->delete();
    }

    /**
     * The stored value, JSON-decoded when flagged as JSON.
     */
    public function decodedValue(): mixed
    {
        return $this->is_json ? json_decode($this->value, true) : $this->value;
    }
}
