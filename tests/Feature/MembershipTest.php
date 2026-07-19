<?php

use Devdojo\Teams\Actions\AddTeamMember;
use Devdojo\Teams\Actions\RemoveTeamMember;
use Devdojo\Teams\Actions\UpdateTeamMemberRole;
use Devdojo\Teams\Events\TeamMemberAdded;
use Devdojo\Teams\Events\TeamMemberRemoved;
use Devdojo\Teams\OwnerRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

it('adds an existing user to the team by email with a role', function () {
    Event::fake([TeamMemberAdded::class]);

    $owner = createUser();
    $newMember = createUser(['email' => 'member@example.com']);
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);

    app(AddTeamMember::class)->add($owner, $team, 'member@example.com', 'editor');

    expect($team->fresh()->hasUser($newMember))->toBeTrue()
        ->and($team->users()->first()->membership->role)->toBe('editor');

    Event::assertDispatched(TeamMemberAdded::class, fn ($event) => $event->user->is($newMember));
});

it('rejects adding an email with no registered user', function () {
    $owner = createUser();
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);

    expect(fn () => app(AddTeamMember::class)->add($owner, $team, 'ghost@example.com', 'member'))
        ->toThrow(ValidationException::class);
});

it('rejects adding a user who already belongs to the team', function () {
    $owner = createUser();
    $member = createUser(['email' => 'dupe@example.com']);
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);
    $team->users()->attach($member, ['role' => 'member']);

    expect(fn () => app(AddTeamMember::class)->add($owner, $team->fresh(), 'dupe@example.com', 'member'))
        ->toThrow(ValidationException::class);
});

it('removes a member and resets their current team', function () {
    Event::fake([TeamMemberRemoved::class]);

    $owner = createUser();
    $member = createUser();
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);
    $team->users()->attach($member, ['role' => 'member']);
    $member->switchTeam($team);

    app(RemoveTeamMember::class)->remove($owner, $team, $member);

    expect($team->fresh()->users()->count())->toBe(0)
        ->and($member->fresh()->current_team_id)->toBeNull();

    Event::assertDispatched(TeamMemberRemoved::class);
});

it('lets a member remove themselves without authorization', function () {
    $owner = createUser();
    $member = createUser();
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);
    $team->users()->attach($member, ['role' => 'member']);

    // A plain member cannot remove others...
    expect(fn () => app(RemoveTeamMember::class)->remove($member, $team, $owner))
        ->toThrow(AuthorizationException::class);

    // ...but may always leave the team themselves.
    app(RemoveTeamMember::class)->remove($member, $team, $member);

    expect($team->fresh()->users()->count())->toBe(0);
});

it('never removes the team owner', function () {
    $owner = createUser();
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);

    expect(fn () => app(RemoveTeamMember::class)->remove($owner, $team, $owner))
        ->toThrow(ValidationException::class, 'You may not remove the team owner.');
});

it('updates an existing member role', function () {
    $owner = createUser();
    $member = createUser();
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);
    $team->users()->attach($member, ['role' => 'member']);

    app(UpdateTeamMemberRole::class)->update($owner, $team, $member->id, 'admin');

    expect($team->fresh()->users()->first()->membership->role)->toBe('admin');
});

it('rejects updating a member to an unknown role', function () {
    $owner = createUser();
    $member = createUser();
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);
    $team->users()->attach($member, ['role' => 'member']);

    expect(fn () => app(UpdateTeamMemberRole::class)->update($owner, $team, $member->id, 'warlord'))
        ->toThrow(ValidationException::class);
});

it('resolves team roles and permissions through the HasTeams trait', function () {
    $owner = createUser();
    $editor = createUser();
    $outsider = createUser();

    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);
    $team->users()->attach($editor, ['role' => 'editor']);

    // Owner: implicit OwnerRole with every permission.
    expect($owner->teamRole($team))->toBeInstanceOf(OwnerRole::class)
        ->and($owner->teamPermissions($team))->toBe(['*'])
        ->and($owner->hasTeamPermission($team, 'delete'))->toBeTrue()
        ->and($owner->hasTeamRole($team, 'anything'))->toBeTrue();

    // Editor: the configured editor role.
    expect($editor->teamRole($team)?->key)->toBe('editor')
        ->and($editor->hasTeamRole($team, 'editor'))->toBeTrue()
        ->and($editor->hasTeamRole($team, 'admin'))->toBeFalse()
        ->and($editor->hasTeamPermission($team, 'update'))->toBeTrue()
        ->and($editor->hasTeamPermission($team, 'delete'))->toBeFalse()
        ->and($editor->teamPermissions($team))->toBe(['read', 'create', 'update']);

    // Outsider: no role, no permissions.
    expect($outsider->teamRole($team))->toBeNull()
        ->and($outsider->teamPermissions($team))->toBe([])
        ->and($outsider->hasTeamPermission($team, 'read'))->toBeFalse();
});

it('tracks team membership and switching through the HasTeams trait', function () {
    $owner = createUser();
    $member = createUser();
    $outsider = createUser();

    $personal = $owner->ownedTeams()->create(['name' => 'Personal', 'personal_team' => true]);
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);
    $team->users()->attach($member, ['role' => 'member']);

    $owner = $owner->fresh();
    $member = $member->fresh();

    expect($owner->ownsTeam($team))->toBeTrue()
        ->and($member->ownsTeam($team))->toBeFalse()
        ->and($member->belongsToTeam($team))->toBeTrue()
        ->and($outsider->belongsToTeam($team))->toBeFalse()
        ->and($owner->personalTeam()?->is($personal))->toBeTrue()
        ->and($owner->allTeams()->pluck('name')->all())->toBe(['Acme', 'Personal']);

    // Switching only works for teams the user belongs to.
    expect($member->switchTeam($team))->toBeTrue()
        ->and($member->isCurrentTeam($team))->toBeTrue()
        ->and($member->currentTeamOrDefault()?->is($team))->toBeTrue()
        ->and($outsider->switchTeam($team))->toBeFalse();

    // Without a current team the default falls back to the personal team.
    expect($owner->currentTeamOrDefault()?->is($personal))->toBeTrue();

    // hasUserWithEmail sees both the owner and members.
    expect($team->hasUserWithEmail($owner->email))->toBeTrue()
        ->and($team->hasUserWithEmail($member->email))->toBeTrue()
        ->and($team->hasUserWithEmail('nobody@example.com'))->toBeFalse();
});
