<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\SocialLogin;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // 1. Find existing SocialLogin
            $social = SocialLogin::where('provider', 'google')
                ->where('provider_user_id', $googleUser->getId())
                ->first();

            if ($social) {
                $user = $social->user;
            } else {
                // 2. If email exists, link to existing user
                $user = User::where('email', $googleUser->getEmail())->first();

                if (! $user) {
                    // 3. Create new user with default role 'user'
                    $role = Role::where('role_name', 'user')->first();

                    $user = User::create([
                        'full_name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'phone_number' => null,
                        'password_hash' => Hash::make(Str::random(16)),
                        'role_id' => $role ? $role->role_id : null,
                    ]);
                }

                // 4. Create SocialLogin entry
                SocialLogin::create([
                    'user_id' => $user->user_id,
                    'provider' => 'google',
                    'provider_user_id' => $googleUser->getId(),
                    'access_token' => $googleUser->token,
                    'refresh_token' => $googleUser->refreshToken ?? null,
                ]);
            }

            // 5. Generate JWT token
            $payload = [
                'iss' => config('app.url'),
                'sub' => $user->user_id,
                'email' => $user->email,
                'role' => $user->role->role_name,
                'iat' => time(),
                'exp' => time() + (60 * 60 * 24 * 7),
            ];

            $token = JWT::encode($payload, config('jwt.secret'), 'HS256');

            // 6. Redirect to frontend callback with token & user info
            $userData = urlencode(json_encode([
                'user_id' => $user->user_id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role->role_name,
            ]));

            return redirect("http://localhost:5173/auth/google/callback?token={$token}&user={$userData}");

        } catch (\Exception $e) {
            // Redirect with error message
            return redirect('http://localhost:5173/auth/google/callback?error='.urlencode($e->getMessage()));
        }
    }
}
