<?php

namespace App\Http\Controllers;

use App\Services\AgentProfileService;
use App\Services\EmailService;
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
                'message' => 'Only agents can access their profile',
            ], 403);
        }

        $profile = $this->agentProfileService->getProfileById($user->user_id);

        if (! $profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $profile,
        ], 200);
    }

    /**
     * Get any agent profile (admin or public)
     */
    public function getAgentProfile($agentId)
    {
        $profile = $this->agentProfileService->getProfileById($agentId);

        if (! $profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $profile,
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
                'message' => 'Only agents can update their profile',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'business_name' => 'sometimes|string|max:200',
            'business_license' => 'nullable|string|max:100',
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

        $updateData = $request->only([
            'business_name',
            'business_license',
            'address',
            'city',
            'working_hours_start',
            'working_hours_end',
        ]);

        $profile = $this->agentProfileService->updateProfile($user->user_id, $updateData);

        if (! $profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated',
            'data' => $profile,
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
                'message' => 'Only admins can update agent status',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:accepted,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $profile = $this->agentProfileService->updateStatus($agentId, $request->status);

        if (! $profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found',
            ], 404);
        }

        // Notifications Area
        $emailService = app(EmailService::class);

        if ($request->status === 'accepted') {
            $payload = [
                'title' => 'Agent Application Approved',
                'subtitle' => 'Welcome to the Exchain Agent Program',
                'message' => 'Congratulations! Your agent application has been accepted. You can now access your agent dashboard.',
                'cta_url' => url('/admin/agents'), // make changes if changed,
                'cta_text' => 'Go to Dashboard'
            ];
        }else{
            $payload = [
                'title' => 'Agent Application Update',
                'subtitle' => 'Your Agent Application Status',
                'message' => 'We appreciate your interest in joining our agent program. Unfortunately, your application was not approved at this time.',
                'cta_url' => url('/admin/agents'), // make changes if changed,
                'cta_text' => 'Go to Dashboard'
            ];

        }

        $emailService->sendAgentApproval($request->user(), $payload);

        // End Notifications Area

        return response()->json([
            'success' => true,
            'message' => "Profile {$request->status}",
            'data' => $profile,
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
        ];

        if ($isAdmin) {
            $status = $request->input('status');
            if ($status) {
                $filters['status'] = $status;
            }

        } else {
            $filters['status'] = 'accepted';
        }

        $agents = $this->agentProfileService->listAgents($filters);

        return response()->json([
            'success' => true,
            'data' => $agents,
            'count' => $agents->count(),
        ], 200);
    }

    public function updateAllCommissions(Request $request)
    {
        $user = $request->user();

        if ($user->role->role_name !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Only admins allowed'], 403);
        }

        $validator = Validator::make($request->all(), [
            'commission_rate' => 'required|numeric|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $updatedCount = $this->agentProfileService->updateAllCommissions($request->commission_rate);

        return response()->json([
            'success' => true,
            'message' => "Commission updated for {$updatedCount} agents",
        ]);
    }

}
