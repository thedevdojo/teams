<?php

namespace Devdojo\Teams\Actions;

use Devdojo\Teams\Events\TeamMemberAdded;
use Devdojo\Teams\Models\Team;
use Devdojo\Teams\Teams;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AddTeamMember
{
    /**
     * Add an existing user (matched by email) directly to the team.
     */
    public function add(Model $user, Team $team, string $email, ?string $role = null): void
    {
        Gate::forUser($user)->authorize('addTeamMember', $team);

        $this->validate($team, $email, $role);

        $newMember = Teams::userModel()::where('email', $email)->firstOrFail();

        $team->users()->attach($newMember, ['role' => $role]);

        TeamMemberAdded::dispatch($team, $newMember);
    }

    /**
     * Validate the member being added.
     */
    protected function validate(Team $team, string $email, ?string $role): void
    {
        Validator::make([
            'email' => $email,
            'role' => $role,
        ], [
            'email' => ['required', 'email', Rule::exists(Teams::userModel(), 'email')],
            'role' => ['nullable', Rule::in(array_keys(Teams::roles()))],
        ], [
            'email.exists' => __('We were unable to find a registered user with this email address.'),
        ])->after(function ($validator) use ($team, $email) {
            if ($team->hasUserWithEmail($email)) {
                $validator->errors()->add('email', __('This user already belongs to the team.'));
            }
        })->validateWithBag('addTeamMember');
    }
}
