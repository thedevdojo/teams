<?php

namespace Devdojo\Teams\Actions;

use Devdojo\Teams\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class CreateTeam
{
    /**
     * Create a new team owned by the given user and switch them onto it.
     *
     * @param  array{name?: string, personal_team?: bool}  $input
     */
    public function create(Model $user, array $input): Team
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
        ])->validate();

        /** @var Team $team */
        $team = $user->ownedTeams()->create([
            'name' => $input['name'],
            'personal_team' => $input['personal_team'] ?? false,
        ]);

        $user->switchTeam($team);

        return $team;
    }
}
