<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $sanitized = [
            'name' => trim(strip_tags((string) ($input['name'] ?? ''))),
            'email' => Str::lower(trim((string) ($input['email'] ?? ''))),
            'password' => $input['password'] ?? '',
            'password_confirmation' => $input['password_confirmation'] ?? null,
            'terms' => $input['terms'] ?? null,
        ];

        Validator::make($sanitized, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return User::create([
            'name' => $sanitized['name'],
            'email' => $sanitized['email'],
            'password' => $sanitized['password'],
            'role' => User::ROLE_STUDENT,
        ]);
    }
}
