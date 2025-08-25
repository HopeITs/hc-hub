<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ContractPolicy
{
    public function view(User $user, Contract $contract): bool
    {
        $isSuperAdmin = DB::table('role_user')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('role_user.user_id', $user->id)
            ->where('roles.name', 'Super Admin')
            ->exists();
        if ($isSuperAdmin) return true;

        $hrNames = ['HR', 'HR Manager'];

        $isHr = method_exists($user, 'roles')
            ? $user->roles()->whereIn('name', $hrNames)->exists()
            : DB::table('role_user')
                ->join('roles','roles.id','=','role_user.role_id')
                ->where('role_user.user_id', $user->id)
                ->whereIn('roles.name', $hrNames)
                ->exists();

        if (! $isHr) return true;

        $userCityId = optional($user->profile)->city_id;
        $contractCityId = optional($contract->position?->center)->city_id;

        return $userCityId && $contractCityId && $userCityId === $contractCityId;
    }
}
