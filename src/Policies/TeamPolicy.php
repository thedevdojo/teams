<?php

namespace Devdojo\Teams\Policies;

use Devdojo\Teams\Models\Team;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the team.
     */
    public function view(Model $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can create teams.
     */
    public function create(Model $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the team.
     */
    public function update(Model $user, Team $team): bool
    {
        return $user->ownsTeam($team) || $user->hasTeamPermission($team, 'update');
    }

    /**
     * Determine whether the user can add team members.
     */
    public function addTeamMember(Model $user, Team $team): bool
    {
        return $user->ownsTeam($team) || $user->hasTeamPermission($team, 'create');
    }

    /**
     * Determine whether the user can update team members' roles.
     */
    public function updateTeamMember(Model $user, Team $team): bool
    {
        return $user->ownsTeam($team) || $user->hasTeamPermission($team, 'update');
    }

    /**
     * Determine whether the user can remove team members.
     */
    public function removeTeamMember(Model $user, Team $team): bool
    {
        return $user->ownsTeam($team) || $user->hasTeamPermission($team, 'delete');
    }

    /**
     * Determine whether the user can delete the team.
     */
    public function delete(Model $user, Team $team): bool
    {
        return $user->ownsTeam($team);
    }
}
