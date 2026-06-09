<?php

namespace Devdojo\Teams\Models;

use Devdojo\Teams\Events\TeamCreated;
use Devdojo\Teams\Events\TeamDeleted;
use Devdojo\Teams\Teams;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'personal_team',
    ];

    /**
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TeamCreated::class,
        'deleted' => TeamDeleted::class,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_team' => 'boolean',
        ];
    }

    /**
     * The user that owns the team.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Teams::userModel(), 'user_id');
    }

    /**
     * All of the users that belong to the team (excluding the owner).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(Teams::userModel(), 'team_user')
            ->using(Teams::membershipModel())
            ->withPivot('role')
            ->withTimestamps()
            ->as('membership');
    }

    /**
     * The pending invitations for the team.
     */
    public function teamInvitations(): HasMany
    {
        return $this->hasMany(Teams::teamInvitationModel());
    }

    /**
     * Every user on the team, owner first.
     *
     * @return Collection<int, Model>
     */
    public function allUsers(): Collection
    {
        $members = $this->users()->get();

        return $this->owner ? $members->prepend($this->owner) : $members;
    }

    /**
     * Determine whether the given user belongs to the team (owner or member).
     */
    public function hasUser(Model $user): bool
    {
        return $this->users->contains($user) || $this->owner()->is($user);
    }

    /**
     * Determine whether a user with the given email belongs to the team.
     */
    public function hasUserWithEmail(string $email): bool
    {
        return $this->allUsers()->contains(fn (Model $user) => $user->email === $email);
    }

    /**
     * Determine whether there is a pending invitation for the given email.
     */
    public function hasPendingInvitation(string $email): bool
    {
        return $this->teamInvitations()->where('email', $email)->exists();
    }

    /**
     * Remove the given user from the team and reset their current team if needed.
     */
    public function removeUser(Model $user): void
    {
        if ((int) $user->current_team_id === (int) $this->id) {
            $user->forceFill(['current_team_id' => null])->save();
        }

        $this->users()->detach($user);
    }

    /**
     * Purge all of the team's resources, then delete the team itself.
     */
    public function purge(): void
    {
        Teams::userModel()::query()
            ->where('current_team_id', $this->id)
            ->update(['current_team_id' => null]);

        $this->users()->detach();
        $this->teamInvitations()->delete();
        $this->delete();
    }
}
