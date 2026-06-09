<?php

namespace Devdojo\Teams\Http\Controllers;

use Devdojo\Teams\Events\TeamMemberAdded;
use Devdojo\Teams\Models\TeamInvitation;
use Devdojo\Teams\Support\Redirects;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamInvitationController extends Controller
{
    /**
     * Accept a team invitation from its signed email link.
     */
    public function accept(Request $request, TeamInvitation $invitation): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user->email === $invitation->email,
            403,
            __('This invitation was sent to a different email address.'),
        );

        $team = $invitation->team;

        if (! $team->hasUser($user)) {
            $team->users()->attach($user, ['role' => $invitation->role]);

            TeamMemberAdded::dispatch($team, $user);
        }

        $user->switchTeam($team);

        $invitation->delete();

        return redirect(Redirects::afterSwitch($team))->with('status', __(
            'You have joined the :team team.',
            ['team' => $team->name],
        ));
    }
}
