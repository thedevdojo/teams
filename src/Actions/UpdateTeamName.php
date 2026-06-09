<?php

namespace Devdojo\Teams\Actions;

use Devdojo\Teams\Events\TeamUpdated;
use Devdojo\Teams\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UpdateTeamName
{
    /**
     * Update the team's name.
     *
     * @param  array{name?: string}  $input
     */
    public function update(Model $user, Team $team, array $input): void
    {
        Gate::forUser($user)->authorize('update', $team);

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
        ])->validate();

        $team->forceFill([
            'name' => $input['name'],
        ])->save();

        TeamUpdated::dispatch($team);
    }
}
