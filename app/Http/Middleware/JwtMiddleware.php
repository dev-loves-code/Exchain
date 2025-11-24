<?php

namespace App\Http\Middleware;

use App\Http\Controllers\AuthController;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class JwtMiddleware
{

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not provided',
            ], 401);
        }

        $decoded = AuthController::decodeToken($token);

        if (! $decoded) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token',
            ], 401);
        }

        // Check if token is expired
        if ($decoded->exp < time()) {
            return response()->json([
                'success' => false,
                'message' => 'Token expired',
            ], 401);
        }

        // Attach user to request
        $user = User::with('role')->find($decoded->sub);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 401);
        }

        $request->merge(['user' => $user]);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}
