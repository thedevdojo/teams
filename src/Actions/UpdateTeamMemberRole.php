<?php

namespace Devdojo\Teams\Actions;

use Devdojo\Teams\Models\Team;
use Devdojo\Teams\Teams;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateTeamMemberRole
{
    /**
     * Update the role of an existing team member.
     */
    public function update(Model $user, Team $team, int $teamMemberId, string $role): void
    {
        Gate::forUser($user)->authorize('updateTeamMember', $team);

        Validator::make([
            'role' => $role,
        ], [
            'role' => ['required', Rule::in(array_keys(Teams::roles()))],
        ])->validate();

        $team->users()->updateExistingPivot($teamMemberId, ['role' => $role]);
    }
}
