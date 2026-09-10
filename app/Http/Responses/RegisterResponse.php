<?php

namespace App\Http\Responses;

use App\Models\User;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            /** @var User|null $user */
            $user = $request->user();

            return response()->json([
                'authenticated' => true,
                'user' => $user instanceof User ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'email_verified_at' => $user->email_verified_at,
                ] : null,
            ], 201);
        }

        return redirect()->intended(config('fortify.home', '/homepage'));
    }
}
