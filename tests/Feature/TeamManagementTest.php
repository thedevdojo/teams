<?php

use Devdojo\Teams\Actions\CreateTeam;
use Devdojo\Teams\Actions\DeleteTeam;
use Devdojo\Teams\Actions\UpdateTeamName;
use Devdojo\Teams\Events\TeamCreated;
use Devdojo\Teams\Events\TeamDeleted;
use Devdojo\Teams\Events\TeamUpdated;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

it('creates a team owned by the user and switches them onto it', function () {
    Event::fake([TeamCreated::class]);

    $user = createUser();

    $team = app(CreateTeam::class)->create($user, ['name' => 'Acme']);

    expect($team->name)->toBe('Acme')
        ->and($team->personal_team)->toBeFalse()
        ->and($team->user_id)->toBe($user->id)
        ->and($user->fresh()->current_team_id)->toBe($team->id);

    Event::assertDispatched(TeamCreated::class, fn ($event) => $event->team->is($team));
});

it('requires a team name to create a team', function () {
    $user = createUser();

    expect(fn () => app(CreateTeam::class)->create($user, ['name' => '']))
        ->toThrow(ValidationException::class);
});

it('lets the owner rename the team', function () {
    Event::fake([TeamUpdated::class]);

    $user = createUser();
    $team = $user->ownedTeams()->create(['name' => 'Old Name', 'personal_team' => false]);

    app(UpdateTeamName::class)->update($user, $team, ['name' => 'New Name']);

    expect($team->fresh()->name)->toBe('New Name');

    Event::assertDispatched(TeamUpdated::class);
});

it('lets a member with the update permission rename the team, but not a plain member', function () {
    $owner = createUser();
    $editor = createUser();
    $member = createUser();

    $team = $owner->ownedTeams()->create(['name' => 'Original', 'personal_team' => false]);
    $team->users()->attach($editor, ['role' => 'editor']);
    $team->users()->attach($member, ['role' => 'member']);

    app(UpdateTeamName::class)->update($editor, $team, ['name' => 'Editor Renamed']);
    expect($team->fresh()->name)->toBe('Editor Renamed');

    expect(fn () => app(UpdateTeamName::class)->update($member, $team, ['name' => 'Member Renamed']))
        ->toThrow(AuthorizationException::class);
});

it('deletes a team and purges its memberships and invitations', function () {
    Event::fake([TeamDeleted::class]);

    $owner = createUser();
    $member = createUser();

    $team = $owner->ownedTeams()->create(['name' => 'Doomed', 'personal_team' => false]);
    $team->users()->attach($member, ['role' => 'member']);
    $team->teamInvitations()->create(['email' => 'invited@example.com', 'role' => 'member']);
    $member->switchTeam($team);

    app(DeleteTeam::class)->delete($owner, $team);

    expect($team->fresh())->toBeNull()
        ->and($team->users()->count())->toBe(0)
        ->and($team->teamInvitations()->count())->toBe(0)
        ->and($member->fresh()->current_team_id)->toBeNull();

    Event::assertDispatched(TeamDeleted::class);
});

it('refuses to delete a personal team', function () {
    $owner = createUser();
    $team = $owner->ownedTeams()->create(['name' => 'Personal', 'personal_team' => true]);

    expect(fn () => app(DeleteTeam::class)->delete($owner, $team))
        ->toThrow(ValidationException::class, 'You may not delete your personal team.');

    expect($team->fresh())->not->toBeNull();
});

it('only allows the owner to delete a team', function () {
    $owner = createUser();
    $admin = createUser();

    $team = $owner->ownedTeams()->create(['name' => 'Locked', 'personal_team' => false]);
    $team->users()->attach($admin, ['role' => 'admin']);

    expect(fn () => app(DeleteTeam::class)->delete($admin, $team))
        ->toThrow(AuthorizationException::class);
});
