<?php

namespace App\Models;

use Database\Factories\PlayerProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A player, owned by an account: the parent's own profile or one per child.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $pop_id
 * @property int $birth_year
 * @property bool $is_child
 * @property Carbon|null $consent_given_at
 * @property int|null $consent_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'pop_id', 'birth_year', 'consent_given_at', 'consent_by_user_id'])]
class PlayerProfile extends Model
{
    /** @use HasFactory<PlayerProfileFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_year' => 'integer',
            'is_child' => 'boolean',
            'consent_given_at' => 'datetime',
        ];
    }

    /**
     * The account that owns this profile.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
