<?php

namespace Devdojo\Teams\Actions;

use Devdojo\Teams\Events\TeamMemberRemoved;
use Devdojo\Teams\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RemoveTeamMember
{
    /**
     * Remove a member from the team. A user may always remove themselves
     * (i.e. "leave" the team); removing anyone else requires authorization.
     */
    public function remove(Model $user, Team $team, Model $teamMember): void
    {
        if (! $user->is($teamMember)) {
            Gate::forUser($user)->authorize('removeTeamMember', $team);
        }

        if ((int) $teamMember->getKey() === (int) $team->user_id) {
            throw ValidationException::withMessages([
                'team' => __('You may not remove the team owner.'),
            ])->errorBag('removeTeamMember');
        }

        $team->removeUser($teamMember);

        TeamMemberRemoved::dispatch($team, $teamMember);
    }
}
