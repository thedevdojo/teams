<?php

use Devdojo\Teams\Models\Membership;
use Devdojo\Teams\Models\Team;
use Devdojo\Teams\Models\TeamInvitation;
use Devdojo\Teams\Role;
use Devdojo\Teams\Support\Redirects;
use Devdojo\Teams\Teams;
use Devdojo\Teams\Tests\Models\User;

it('builds the configured roles keyed by identifier', function () {
    $roles = Teams::roles();

    expect($roles)->toHaveKeys(['admin', 'editor', 'member'])
        ->and($roles['admin'])->toBeInstanceOf(Role::class)
        ->and($roles['admin']->name)->toBe('Administrator')
        ->and($roles['admin']->permissions)->toBe(['create', 'read', 'update', 'delete'])
        ->and($roles['member']->permissions)->toBe(['read']);
});

it('finds a role by key and returns null for unknown or null keys', function () {
    expect(Teams::findRole('editor')?->name)->toBe('Editor')
        ->and(Teams::findRole('nope'))->toBeNull()
        ->and(Teams::findRole(null))->toBeNull();
});

it('treats the first configured role as the default', function () {
    expect(Teams::defaultRole()?->key)->toBe('admin');
});

it('flattens the distinct permissions across all roles', function () {
    expect(Teams::permissions())->toBe(['create', 'read', 'update', 'delete']);
});

it('registers and flushes runtime roles', function () {
    Teams::role('billing', 'Billing Manager', ['invoices.read'], 'Manages invoices.');

    expect(Teams::roles())->toHaveKey('billing')
        ->and(Teams::findRole('billing')?->hasPermission('invoices.read'))->toBeTrue()
        ->and(Teams::permissions())->toContain('invoices.read');

    Teams::flushRoles();

    expect(Teams::roles())->not->toHaveKey('billing');
});

it('resolves the user model from the teams config, then the auth config', function () {
    // The TestCase points auth.providers.users.model at the stub User.
    expect(Teams::userModel())->toBe(User::class);

    config(['teams.user_model' => 'App\\Models\\CustomUser']);

    expect(Teams::userModel())->toBe('App\\Models\\CustomUser');
});

it('resolves the model classes from config with sensible defaults', function () {
    expect(Teams::teamModel())->toBe(Team::class)
        ->and(Teams::membershipModel())->toBe(Membership::class)
        ->and(Teams::teamInvitationModel())->toBe(TeamInvitation::class)
        ->and(Teams::newTeam())->toBeInstanceOf(Team::class)
        ->and(Teams::newTeamInvitation())->toBeInstanceOf(TeamInvitation::class);
});

it('reads the feature toggles from config', function () {
    expect(Teams::hasPersonalTeams())->toBeTrue()
        ->and(Teams::sendsInvitations())->toBeTrue();

    config(['teams.features.personal_teams' => false, 'teams.features.invitations' => false]);

    expect(Teams::hasPersonalTeams())->toBeFalse()
        ->and(Teams::sendsInvitations())->toBeFalse();
});

it('resolves redirect patterns with the team key substituted', function () {
    $user = createUser();
    $team = $user->ownedTeams()->create(['name' => 'Redirects', 'personal_team' => false]);

    expect(Redirects::afterSwitch($team))->toBe('/teams/'.$team->id)
        ->and(Redirects::afterCreate($team))->toBe('/teams/'.$team->id);

    config(['teams.redirect_after_create' => '/dashboard']);

    expect(Redirects::afterCreate($team))->toBe('/dashboard');
});
