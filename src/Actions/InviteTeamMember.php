<?php

namespace Devdojo\Teams\Actions;

use Devdojo\Teams\Events\TeamMemberInvited;
use Devdojo\Teams\Mail\TeamInvitationMail;
use Devdojo\Teams\Models\Team;
use Devdojo\Teams\Models\TeamInvitation;
use Devdojo\Teams\Teams;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class InviteTeamMember
{
    /**
     * Invite a user to the team by email and send them an invitation link.
     */
    public function invite(Model $user, Team $team, string $email, ?string $role = null): TeamInvitation
    {
        Gate::forUser($user)->authorize('addTeamMember', $team);

        $this->validate($team, $email, $role);

        /** @var TeamInvitation $invitation */
        $invitation = $team->teamInvitations()->create([
            'email' => $email,
            'role' => $role,
        ]);

        TeamMemberInvited::dispatch($invitation);

        Mail::to($email)->send(new TeamInvitationMail($invitation));

        return $invitation;
    }

    /**
     * Validate the invitation.
     */
    protected function validate(Team $team, string $email, ?string $role): void
    {
        Validator::make([
            'email' => $email,
            'role' => $role,
        ], [
            'email' => ['required', 'email'],
            'role' => ['nullable', Rule::in(array_keys(Teams::roles()))],
        ])->after(function ($validator) use ($team, $email) {
            if ($team->hasUserWithEmail($email)) {
                $validator->errors()->add('email', __('This user already belongs to the team.'));
            } elseif ($team->hasPendingInvitation($email)) {
                $validator->errors()->add('email', __('This user has already been invited to the team.'));
            }
        })->validateWithBag('addTeamMember');
    }
}
