<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\SocialLogin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Firebase\JWT\JWT;

class GitHubAuthController extends Controller
{
/** 
 * @return \Laravel\Socialite\Contracts\Provider 
 */
  public function redirectToGitHub()
    {
    return Socialite::driver('github')->stateless()->redirect();
    }

    // Handle GitHub callback
    public function handleGitHubCallback()
    {
        try {
            // Get user info from GitHub
    $githubUser = Socialite::driver('github')->stateless()->user();

            // 1️⃣ Check if this social account exists
            $social = SocialLogin::where('provider', 'github')
                ->where('provider_user_id', $githubUser->getId())
                ->first();

            if ($social) {
                $user = $social->user;
            } else {
                // 2️⃣ Check if a user with this email exists
                $user = User::where('email', $githubUser->getEmail())->first();

                if (!$user) {
                    // 3️⃣ Create a new user with default role 'user'
                    $role = Role::where('role_name', 'user')->first();

                    $user = User::create([
                        'full_name' => $githubUser->getName() ?? $githubUser->getNickname(),
                        'email' => $githubUser->getEmail(),
                        'phone_number' => null,
                        'password_hash' => Hash::make(Str::random(16)),
                        'role_id' => $role ? $role->role_id : null,
                    ]);
                }

                // 4️⃣ Save the social login record
                SocialLogin::create([
                    'user_id' => $user->user_id,
                    'provider' => 'github',
                    'provider_user_id' => $githubUser->getId(),
                    'access_token' => $githubUser->token,
                    'refresh_token' => $githubUser->refreshToken ?? null,
                ]);
            }

            // 5️⃣ Generate JWT token for API usage
            $payload = [
                'iss' => config('app.url'),
                'sub' => $user->user_id,
                'email' => $user->email,
                'role' => $user->role->role_name,
                'iat' => time(),
                'exp' => time() + (60 * 60 * 24 * 7), // 1 week
            ];

            $token = JWT::encode($payload, config('jwt.secret'), 'HS256');

            // 6️⃣ Redirect to frontend with token
return redirect("http://localhost:5173/login?token={$token}");
        } catch (\Exception $e) {
            return redirect("http://localhost:5173/login?error=GitHub login failed");
        }
    }
}