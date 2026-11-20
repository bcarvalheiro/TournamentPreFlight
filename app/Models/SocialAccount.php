<?php

namespace App\Models;

use Database\Factories\SocialAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A Google, Apple or Discord identity linked to an account.
 *
 * @property int $id
 * @property int $user_id
 * @property string $provider google, apple or discord
 * @property string $provider_user_id
 * @property string|null $provider_email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['provider', 'provider_user_id', 'provider_email'])]
class SocialAccount extends Model
{
    /** @use HasFactory<SocialAccountFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
