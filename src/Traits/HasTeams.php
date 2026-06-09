<?php

namespace Devdojo\Teams\Traits;

use Devdojo\Teams\Models\Team;
use Devdojo\Teams\OwnerRole;
use Devdojo\Teams\Role;
use Devdojo\Teams\Teams;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

trait HasTeams
{
    /**
     * The team the user is currently viewing.
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Teams::teamModel(), 'current_team_id');
    }

    /**
     * Teams the user owns.
     */
    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Teams::teamModel(), 'user_id');
    }

    /**
     * Teams the user belongs to (excluding owned teams).
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Teams::teamModel(), 'team_user')
            ->using(Teams::membershipModel())
            ->withPivot('role')
            ->withTimestamps()
            ->as('membership');
    }

    /**
     * Every team the user owns or is a member of.
     *
     * @return Collection<int, Team>
     */
    public function allTeams(): Collection
    {
        return $this->ownedTeams->merge($this->teams)->sortBy('name')->values();
    }

    /**
     * The user's personal team.
     */
    public function personalTeam(): ?Team
    {
        return $this->ownedTeams->firstWhere('personal_team', true);
    }

    /**
     * Resolve the current team, falling back to the personal/first team.
     */
    public function currentTeamOrDefault(): ?Team
    {
        if ($this->current_team_id && $this->currentTeam) {
            return $this->currentTeam;
        }

        return $this->personalTeam() ?? $this->allTeams()->first();
    }

    /**
     * Determine whether the user owns the given team.
     */
    public function ownsTeam(?Team $team): bool
    {
        if ($team === null) {
            return false;
        }

        return $this->getKey() === $team->user_id;
    }

    /**
     * Determine whether the user owns or is a member of the given team.
     */
    public function belongsToTeam(?Team $team): bool
    {
        if ($team === null) {
            return false;
        }

        return $this->ownsTeam($team)
            || $this->teams->contains(fn (Team $t) => $t->getKey() === $team->getKey());
    }

    /**
     * Determine whether the given team is the user's current team.
     */
    public function isCurrentTeam(?Team $team): bool
    {
        return $team !== null && (int) $this->current_team_id === (int) $team->getKey();
    }

    /**
     * Get the user's role on the given team.
     */
    public function teamRole(Team $team): ?Role
    {
        if ($this->ownsTeam($team)) {
            return new OwnerRole;
        }

        if (! $this->belongsToTeam($team)) {
            return null;
        }

        $membership = $this->teams->firstWhere('id', $team->getKey())?->membership;

        return Teams::findRole($membership?->role);
    }

    /**
     * Determine whether the user has the given role on the team.
     */
    public function hasTeamRole(Team $team, string $role): bool
    {
        if ($this->ownsTeam($team)) {
            return true;
        }

        return $this->teamRole($team)?->key === $role;
    }

    /**
     * Get every permission the user has on the team.
     *
     * @return array<int, string>
     */
    public function teamPermissions(Team $team): array
    {
        if ($this->ownsTeam($team)) {
            return ['*'];
        }

        return $this->teamRole($team)?->permissions ?? [];
    }

    /**
     * Determine whether the user has the given permission on the team.
     */
    public function hasTeamPermission(Team $team, string $permission): bool
    {
        if ($this->ownsTeam($team)) {
            return true;
        }

        return (bool) $this->teamRole($team)?->hasPermission($permission);
    }

    /**
     * Switch the user's current team. Returns false if they don't belong to it.
     */
    public function switchTeam(Team $team): bool
    {
        if (! $this->belongsToTeam($team)) {
            return false;
        }

        $this->forceFill(['current_team_id' => $team->getKey()])->save();

        $this->setRelation('currentTeam', $team);

        return true;
    }
}
