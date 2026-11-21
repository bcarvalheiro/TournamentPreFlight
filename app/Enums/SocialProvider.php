<?php

namespace App\Enums;

use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * The social sign-in providers (M06; Apple in M13). The value is the
 * Socialite driver name and the `{provider}` in the auth routes.
 */
enum SocialProvider: string
{
    case Google = 'google';
    case Discord = 'discord';
    case X = 'x';

    /**
     * The providers whose credentials are configured, so their buttons work.
     *
     * @return list<self>
     */
    public static function enabled(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $provider): bool => filled(config("services.{$provider->value}.client_id")),
        ));
    }

    /**
     * Whether the provider vouches for the identity's email: Google and Discord
     * flag it in their raw data; X only ever sends a confirmed email.
     */
    public function confirmsEmail(SocialiteUser $identity): bool
    {
        if ($identity->getEmail() === null) {
            return false;
        }

        $raw = $identity instanceof AbstractUser ? $identity->getRaw() : [];

        return match ($this) {
            self::Google => ($raw['email_verified'] ?? false) === true,
            self::Discord => ($raw['verified'] ?? false) === true,
            self::X => true,
        };
    }
}
