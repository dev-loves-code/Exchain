<?php

namespace App\Http\Controllers;

use App\Models\AgentProfile;
use App\Models\Role;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function registerUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::where('role_name', 'user')->first();
        if (! $role) {
            return response()->json([
                'success' => false,
                'message' => 'User role not found',
            ], 400);
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password_hash' => Hash::make($request->password),
            'role_id' => $role->role_id,
        ]);

        $token = $this->generateToken($user);

        // Notification Section
        $emailService = app(EmailService::class);
        $data = [
          'success' => true,
            'title' => 'Welcome to Exchain',
            'subtitle' => 'Your account has been created successfully!',
            'cta_url' => url('/auth/login'),
            'cta_text' => 'Login Now',

        ];

        $emailService->sendUserSignup($user, $data);

        //End Notification Section

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => [
                    'user_id' => $user->user_id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'role' => 'user',
                ],
                'token' => $token,
            ],
        ], 201);
    }

    public function registerAgent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',

            'business_name' => 'required|string|max:200',
            'business_license' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'working_hours_start' => 'nullable|date_format:H:i',
            'working_hours_end' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::where('role_name', 'agent')->first();

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password_hash' => Hash::make($request->password),
            'role_id' => $role->role_id,
        ]);

        AgentProfile::create([
            'agent_id' => $user->user_id,
            'business_name' => $request->business_name,
            'business_license' => $request->business_license,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address,
            'city' => $request->city,
            'working_hours_start' => $request->working_hours_start,
            'working_hours_end' => $request->working_hours_end,
            'commission_rate' => 5,
            'status' => 'pending',
        ]);

        // Notifications Area

        $emailService = app(EmailService::class);

        $agentPayload = [
            'subject' => 'Welcome to Exchain!',
            'title' => 'Welcome!',
            'subtitle' => 'Your agent account for '. $request->business_name.  '  is being processed.',
            'message' => 'Once processed you can manage your services and profile.',
            'cta_text' => 'Go to Dashboard',
            'cta_url' => url('/auth/login'),
        ];

        $adminPayload = [
            'subject' => 'New Agent Signed Up',
            'title' => 'New Agent Alert',
            'subtitle' => $user->full_name . ' registered as '. $request->business_name,
            'message' => 'Agent Email: ' . $user->email . ' Please review their application.',
            'cta_text' => 'View Agent',
            'cta_url' => url('/admin/agents'), // make changes if changed
        ];


        $emailService->sendAgentSignup($user, $agentPayload);
        $admin = User::where('role_id',1)->firstOrFail();
        $emailService->sendAgentSignup($admin, $adminPayload);


        //End Notification Area


        return response()->json([
            'success' => true,
            'message' => 'Agent registration submitted.',
            'data' => [
                'user_id' => $user->user_id,
                'email' => $user->email,
                'status' => 'pending',
            ],
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Check agent status if role = agent
        if ($user->role->role_name === 'agent') {
            $agentProfile = $user->agentProfile; // assuming relation User->hasOne(AgentProfile::class, 'agent_id', 'user_id')

            if (! $agentProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agent profile not found',
                ], 404);
            }

            if ($agentProfile->status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your agent account is still under review. Please wait for approval.',
                ], 403);
            }

            if ($agentProfile->status === 'rejected') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your agent registration was rejected. Contact support for more information.',
                ], 403);
            }
        }

        // Passed checks → generate token
        $token = $this->generateToken($user);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'user_id' => $user->user_id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'role' => $user->role->role_name,
                ],
                'token' => $token,
            ],
        ], 200);
    }

    /**
     * Logout user (client-side token removal)
     */
    public function logout(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ], 200);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'user_id' => $user->user_id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'role' => $user->role->role_name,
                ],
            ],
        ], 200);
    }

    /**
     * Generate JWT token
     */
    private function generateToken(User $user)
    {
        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->user_id,
            'email' => $user->email,
            'role' => $user->role->role_name,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24 * 7), // 7 days
        ];

        return JWT::encode($payload, config('jwt.secret'), 'HS256');
    }

    /**
     * Decode JWT token
     */
    public static function decodeToken($token)
    {
        try {
            return JWT::decode($token, new Key(config('jwt.secret'), 'HS256'));
        } catch (\Exception $e) {
            return null;
        }
    }

    
public function getUser(Request $request, $id)
{
    $authenticatedUser = $request->user();
    
    
    $allowedRoles = ['admin', 'agent'];
    $authenticatedRole = $authenticatedUser->role->role_name;
    
    
    if ($authenticatedUser->user_id != $id && !in_array($authenticatedRole, $allowedRoles)) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized to view this user',
        ], 403);
    }
    
    
    $user = User::with('role')->find($id);
    
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found',
        ], 404);
    }
    
    return response()->json([
        'success' => true,
        'data' => [
            'user' => [
                'user_id' => $user->user_id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'role' => $user->role->role_name,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ],
    ], 200);
}
}