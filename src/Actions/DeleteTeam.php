<?php

namespace Devdojo\Teams\Actions;

use Devdojo\Teams\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeleteTeam
{
    /**
     * Permanently delete a team and all of its memberships and invitations.
     */
    public function delete(Model $user, Team $team): void
    {
        Gate::forUser($user)->authorize('delete', $team);

        if ($team->personal_team) {
            throw ValidationException::withMessages([
                'team' => __('You may not delete your personal team.'),
            ])->errorBag('deleteTeam');
        }

        $team->purge();
    }
}
