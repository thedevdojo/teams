<?php

namespace Devdojo\Teams\Listeners;

use Devdojo\Teams\Teams;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;

class CreatePersonalTeam
{
    /**
     * Give every newly-registered user a personal team and make it current.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;

        if (! Teams::hasPersonalTeams()) {
            return;
        }

        // Only act on User models that have been wired with the HasTeams trait.
        if (! method_exists($user, 'ownedTeams') || ! method_exists($user, 'personalTeam')) {
            return;
        }

        if ($user->personalTeam()) {
            return;
        }

        $team = $user->ownedTeams()->create([
            'name' => Str::of($user->name ?: 'My')->explode(' ')->first().'\'s Team',
            'personal_team' => true,
        ]);

        $user->switchTeam($team);
    }
}
