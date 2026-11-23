<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $age_confirmed_at
 * @property bool $is_admin
 * @property array{reminders: bool} $notification_settings
 * @property string|null $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'age_confirmed_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Defaults a new object has before it is saved, in their stored (JSON) form.
     * The migration has the same default for rows inserted without the model.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'notification_settings' => '{"reminders":true}',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'age_confirmed_at' => 'datetime',
            'is_admin' => 'boolean',
            'notification_settings' => 'array',
            'password' => 'hashed',
        ];
    }

    /**
     * Whether this account may use the site (the player gate): a verified
     * email and at least one player profile. A password is optional.
     */
    public function isPlayerReady(): bool
    {
        return $this->hasVerifiedEmail() && $this->playerProfiles()->exists();
    }

    /**
     * The player profiles this account holds (its own and its children's).
     *
     * @return HasMany<PlayerProfile, $this>
     */
    public function playerProfiles(): HasMany
    {
        return $this->hasMany(PlayerProfile::class);
    }

    /**
     * The Google, Discord or X identities linked to this account.
     *
     * @return HasMany<SocialAccount, $this>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }
}
