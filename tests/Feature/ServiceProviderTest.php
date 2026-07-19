<?php

use Devdojo\Teams\Models\Membership;
use Devdojo\Teams\Models\Team;
use Devdojo\Teams\Models\TeamInvitation;
use Devdojo\Teams\Teams;
use Devdojo\Teams\TeamsFacade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

it('boots the service provider and merges the config', function () {
    expect(config('teams.features.personal_teams'))->toBeTrue()
        ->and(config('teams.features.invitations'))->toBeTrue()
        ->and(config('teams.models.team'))->toBe(Team::class)
        ->and(config('teams.models.membership'))->toBe(Membership::class)
        ->and(config('teams.models.team_invitation'))->toBe(TeamInvitation::class)
        ->and(config('teams.prefix'))->toBe('teams');
});

it('binds the devdojo.teams singleton behind the facade', function () {
    expect(app('devdojo.teams'))->toBeInstanceOf(Teams::class)
        ->and(TeamsFacade::hasPersonalTeams())->toBeTrue();
});

it('registers the package views', function () {
    expect(view()->exists('teams::components.card'))->toBeTrue()
        ->and(view()->exists('teams::mail.team-invitation'))->toBeTrue();
});

it('registers the signed invitation accept route', function () {
    expect(Route::has('teams.invitations.accept'))->toBeTrue();

    $route = Route::getRoutes()->getByName('teams.invitations.accept');

    expect($route->middleware())->toContain('signed', 'auth', 'web');
});

it('runs the package migrations', function () {
    expect(Schema::hasTable('teams'))->toBeTrue()
        ->and(Schema::hasTable('team_user'))->toBeTrue()
        ->and(Schema::hasTable('team_invitations'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'current_team_id'))->toBeTrue();
});
