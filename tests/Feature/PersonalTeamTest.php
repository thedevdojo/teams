<?php

use Devdojo\Teams\Models\Team;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\User;

it('creates a personal team when a user registers', function () {
    $user = createUser(['name' => 'Tony Stark']);

    event(new Registered($user));

    $personal = $user->fresh()->personalTeam();

    expect($personal)->not->toBeNull()
        ->and($personal->name)->toBe("Tony's Team")
        ->and($personal->personal_team)->toBeTrue()
        ->and($user->fresh()->current_team_id)->toBe($personal->id);
});

it('does not create a second personal team for the same user', function () {
    $user = createUser();

    event(new Registered($user));
    event(new Registered($user->fresh()));

    expect($user->fresh()->ownedTeams()->where('personal_team', true)->count())->toBe(1);
});

it('does not create a personal team when the feature is disabled', function () {
    config(['teams.features.personal_teams' => false]);

    $user = createUser();

    event(new Registered($user));

    expect($user->fresh()->ownedTeams()->count())->toBe(0);
});

it('ignores user models that are not wired with the HasTeams trait', function () {
    $user = new class extends User
    {
        protected $table = 'users';

        protected $guarded = [];
    };

    $user->forceFill(['name' => 'Plain', 'email' => 'plain@example.com'])->save();

    event(new Registered($user));

    expect(Team::count())->toBe(0);
});
