<?php

namespace App\Models;

use Database\Factories\PlayerProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

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
     * A profile that may be under 13 at some point this calendar year (it turns
     * 13 or younger) is a child profile, and it needs the parent's recorded
     * consent (CLAUDE.md rule 2). Only the birth year is known, so this errs
     * towards asking. Checked on every model save (not on bulk queries).
     */
    protected static function booted(): void
    {
        static::saving(function (PlayerProfile $profile): void {
            $profile->is_child = now('Europe/Lisbon')->year - $profile->birth_year <= 13;

            if ($profile->is_child && ($profile->consent_given_at === null || $profile->consent_by_user_id === null)) {
                throw ValidationException::withMessages([
                    'consent' => 'A parent must give consent for a player under 13.',
                ]);
            }
        });
    }

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

    /**
     * This profile's going answers.
     *
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
