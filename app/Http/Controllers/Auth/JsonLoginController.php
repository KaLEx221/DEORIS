<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JsonLoginController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return response()->json([
                'authenticated' => false,
                'message' => 'Invalid credentials',
            ], 422);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'two_factor' => false,
            'authenticated' => true,
            'user' => $this->publicUser($user),
        ]);
    }

    public function register(Request $request, CreateNewUser $creator): JsonResponse
    {
        $user = $creator->create($request->all());

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'authenticated' => true,
            'user' => $this->publicUser($user),
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['authenticated' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function publicUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'email_verified_at' => $user->email_verified_at,
            'admission_status' => $user->admission_status,
            'enrollment_status' => $user->enrollment_status,
            'clearcheck_passed' => $user->clearcheck_passed,
        ];
    }
}
