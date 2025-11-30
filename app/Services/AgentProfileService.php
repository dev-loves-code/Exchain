<?php

namespace App\Services;

use App\Models\AgentProfile;

class AgentProfileService
{
    public function getProfileById($agentId)
    {
        $profile = AgentProfile::with('agent')->where('agent_id', $agentId)->first();

        if (! $profile) {
            return null;
        }

        return $this->formatProfileData($profile);
    }


    public function updateProfile($agentId, array $data)
    {
        $profile = AgentProfile::where('agent_id', $agentId)->first();

        if (! $profile) {
            return null;
        }

        $profile->update($data);
        $profile = $profile->fresh(['agent']);

        return $this->formatProfileData($profile);
    }

    public function updateStatus($agentId, string $status)
    {
        $profile = AgentProfile::where('agent_id', $agentId)->first();

        if (! $profile) {
            return null;
        }

        $profile->update(['status' => $status]);
        $profile = $profile->fresh(['agent']);

        return [
            'agent_id' => $profile->agent_id,
            'full_name' => $profile->agent->full_name,
            'email' => $profile->agent->email,
            'business_name' => $profile->business_name,
            'status' => $profile->status,
        ];
    }

    public function listAgents(array $filters = [])
    {
        $query = AgentProfile::with('agent');

        if (! empty($filters['city'])) {
            $query->where('city', 'LIKE', '%'.$filters['city'].'%');
        }

        if (! empty($filters['name'])) {
            $query->whereHas('agent', fn ($q) => $q->where('full_name', 'LIKE', '%'.$filters['name'].'%'));
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $agents = $query->get();

        return $agents->map(fn ($profile) => $this->formatProfileData($profile));
    }

    private function formatProfileData($profile)
    {
        return [
            'agent_id' => $profile->agent_id,
            'full_name' => $profile->agent->full_name,
            'email' => $profile->agent->email,
            'phone_number' => $profile->agent->phone_number,
            'profile_picture' => $profile->agent->profile_picture ?? null,
            'business_name' => $profile->business_name,
            'business_license' => $profile->business_license,
            'latitude' => $profile->latitude,
            'longitude' => $profile->longitude,
            'address' => $profile->address,
            'city' => $profile->city,
            'working_hours_start' => $profile->working_hours_start,
            'working_hours_end' => $profile->working_hours_end,
            'commission_rate' => $profile->commission_rate,
            'status' => $profile->status,
            'created_at' => $profile->created_at,
        ];
    }

    public function updateAllCommissions($rate)
    {
        return AgentProfile::query()->update(['commission_rate' => $rate]);
    }

    public function updateAgentCommission($agentId, $rate)
    {
        $profile = AgentProfile::where('agent_id', $agentId)->first();

        if (! $profile) {
            return null;
        }

        $profile->update(['commission_rate' => $rate]);

        return $this->formatProfileData($profile->fresh(['agent']));
    }
}
