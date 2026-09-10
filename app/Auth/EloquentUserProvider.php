<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider as BaseEloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Throwable;

class EloquentUserProvider extends BaseEloquentUserProvider
{
    /**
     * Accept bcrypt, argon, and leftover plaintext hashes. Laravel's bcrypt
     * driver throws "This password does not use the Bcrypt algorithm" when
     * HASH_VERIFY is on and the stored value was hashed by another algorithm.
     */
    public function validateCredentials(UserContract $user, #[\SensitiveParameter] array $credentials): bool
    {
        $plain = $credentials['password'] ?? null;
        $hashed = $user->getAuthPassword();

        if (! is_string($plain) || $plain === '' || ! is_string($hashed) || $hashed === '') {
            return false;
        }

        try {
            if ($this->hasher->check($plain, $hashed)) {
                return true;
            }
        } catch (RuntimeException) {
            // Stored hash is not bcrypt; fall through to password_verify / plaintext.
        }

        if (password_verify($plain, $hashed)) {
            return true;
        }

        return strlen($hashed) < 60 && hash_equals($hashed, $plain);
    }

    public function rehashPasswordIfRequired(UserContract $user, #[\SensitiveParameter] array $credentials, bool $force = false): void
    {
        $plain = $credentials['password'] ?? null;
        if (! is_string($plain) || $plain === '') {
            return;
        }

        $hashed = (string) $user->getAuthPassword();
        $configured = false;

        try {
            $configured = Hash::verifyConfiguration($hashed);
        } catch (Throwable) {
            $configured = false;
        }

        if (! $force && $configured && ! $this->hasher->needsRehash($hashed)) {
            return;
        }

        // Plaintext assignment lets the User `hashed` cast hash once with bcrypt.
        $user->forceFill([
            $user->getAuthPasswordName() => $plain,
        ])->save();
    }
}
