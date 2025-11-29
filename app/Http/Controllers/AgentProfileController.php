<?php

namespace App\Http\Controllers;

use App\Services\AgentProfileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
class AgentProfileController extends Controller
{
    protected $agentProfileService;

    public function __construct(AgentProfileService $agentProfileService)
    {
        $this->agentProfileService = $agentProfileService;
    }

    /**
     * Agent gets their own profile
     */
    public function getPersonalProfile(Request $request)
    {
        $user = $request->user();

        if ($user->role->role_name !== 'agent') {
            return response()->json([
                'success' => false,
                'message' => 'Only agents can access their profile'
            ], 403);
        }

        $profile = $this->agentProfileService->getProfileById($user->user_id);

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $profile
        ], 200);
    }

    /**
     * Get any agent profile (admin or public)
     */
    public function getAgentProfile($agentId)
    {
        $profile = $this->agentProfileService->getProfileById($agentId);

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $profile
        ], 200);
    }

    /**
     * Agent updates their own profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if ($user->role->role_name !== 'agent') {
            return response()->json([
                'success' => false,
                'message' => 'Only agents can update their profile'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'business_name' => 'sometimes|string|max:200',
            'business_license' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'working_hours_start' => 'nullable|date_format:H:i',
            'working_hours_end' => 'nullable|date_format:H:i',
            'commission_rate' => 'nullable|numeric|between:1,6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $request->only([
            'business_name',
            'business_license',
            'latitude',
            'longitude',
            'address',
            'city',
            'working_hours_start',
            'working_hours_end',
            'commission_rate',
        ]);

        $profile = $this->agentProfileService->updateProfile($user->user_id, $updateData);

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated',
            'data' => $profile
        ], 200);
    }

    /**
     * Admin accepts or rejects an agent
     */
    public function updateStatus(Request $request, $agentId)
    {
        $user = $request->user();

        if ($user->role->role_name !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can update agent status'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:accepted,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = $this->agentProfileService->updateStatus($agentId, $request->status);

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => "Profile {$request->status}",
            'data' => $profile
        ], 200);
    }

    /**
     * List agents with optional filters (city, name)
     */
public function listAgents(Request $request)
{
    $user = $request->user();
    $isAdmin = $user && $user->role->role_name === 'admin';

    $filters = [
        'city' => $request->input('city'),
        'name' => $request->input('name'),
        'status' => $isAdmin ? $request->input('status') : 'accepted',
    ];

    $agents = $this->agentProfileService->listAgents($filters);

    $agentsCollection = collect($agents)->map(function ($agent) {
        $profile = is_array($agent) ? $agent : $agent->toArray();

        $totalCommission = \App\Models\CashOperation::where('agent_id', $profile['agent_id'] ?? $profile['user_id'])
            ->where('status', 'approved')
            ->sum(DB::raw('ROUND(amount * agent_commission / 100, 2)'));

        return array_merge($profile, [
            'user_id' => $profile['agent_id'] ?? $profile['user_id'],
            'full_name' => $profile['full_name'] ?? ($profile['name'] ?? 'N/A'),
            'email' => $profile['email'] ?? 'N/A',
            'phone' => $profile['phone'] ?? 'N/A',
            'total_commission' => $totalCommission,
        ]);
    });

    return response()->json([
        'success' => true,
        'data' => $agentsCollection,
        'count' => $agentsCollection->count(),
    ], 200);
}
}