<?php

use Devdojo\Teams\Mail\TeamInvitationMail;
use Devdojo\Teams\Models\Team;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;

it('creates a team from the create-team-form component', function () {
    $user = createUser();

    $this->actingAs($user);

    Volt::test('teams.create-team-form')
        ->set('name', 'Volt Team')
        ->call('create')
        ->assertHasNoErrors()
        ->assertRedirect('/teams/'.$user->fresh()->ownedTeams()->first()->id);

    $team = $user->fresh()->ownedTeams()->first();

    expect($team->name)->toBe('Volt Team')
        ->and($team->personal_team)->toBeFalse()
        ->and($user->fresh()->current_team_id)->toBe($team->id);
});

it('validates the team name in the create-team-form component', function () {
    $this->actingAs(createUser());

    Volt::test('teams.create-team-form')
        ->set('name', '')
        ->call('create')
        ->assertHasErrors(['name']);

    expect(Team::count())->toBe(0);
});

it('renames the team from the update-team-name-form component', function () {
    $owner = createUser();
    $team = $owner->ownedTeams()->create(['name' => 'Before', 'personal_team' => false]);

    $this->actingAs($owner);

    Volt::test('teams.update-team-name-form', ['team' => $team])
        ->assertSet('name', 'Before')
        ->set('name', 'After')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('team-name-saved');

    expect($team->fresh()->name)->toBe('After');
});

it('invites a member from the team-member-manager component', function () {
    Mail::fake();

    $owner = createUser();
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);

    $this->actingAs($owner);

    Volt::test('teams.team-member-manager', ['team' => $team])
        ->assertSet('role', 'admin') // the default role is the first configured one
        ->set('email', 'newbie@example.com')
        ->set('role', 'member')
        ->call('addTeamMember')
        ->assertHasNoErrors()
        ->assertDispatched('member-added')
        ->assertSet('email', '');

    expect($team->fresh()->hasPendingInvitation('newbie@example.com'))->toBeTrue();

    Mail::assertSent(TeamInvitationMail::class);
});

it('cancels an invitation and manages member roles from the team-member-manager component', function () {
    $owner = createUser();
    $member = createUser();
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);
    $team->users()->attach($member, ['role' => 'member']);
    $invitation = $team->teamInvitations()->create(['email' => 'pending@example.com', 'role' => 'member']);

    $this->actingAs($owner);

    $component = Volt::test('teams.team-member-manager', ['team' => $team]);

    $component->call('updateRole', $member->id, 'editor');
    expect($team->fresh()->users()->first()->membership->role)->toBe('editor');

    $component->call('cancelInvitation', $invitation->id);
    expect($invitation->fresh())->toBeNull();

    $component->call('removeMember', $member->id);
    expect($team->fresh()->users()->count())->toBe(0);
});

it('lets a member leave the team from the team-member-manager component', function () {
    $owner = createUser();
    $member = createUser();
    $personal = $member->ownedTeams()->create(['name' => 'Home', 'personal_team' => true]);
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);
    $team->users()->attach($member, ['role' => 'member']);
    $member->switchTeam($team);

    $this->actingAs($member);

    Volt::test('teams.team-member-manager', ['team' => $team])
        ->call('leaveTeam')
        ->assertRedirect('/teams/'.$personal->id);

    expect($team->fresh()->users()->count())->toBe(0)
        ->and($member->fresh()->current_team_id)->toBe($personal->id);
});

it('switches teams from the team-switcher component', function () {
    $user = createUser();
    $teamA = $user->ownedTeams()->create(['name' => 'Alpha', 'personal_team' => false]);
    $teamB = $user->ownedTeams()->create(['name' => 'Beta', 'personal_team' => false]);
    $user->switchTeam($teamA);

    $this->actingAs($user->fresh());

    Volt::test('teams.team-switcher')
        ->call('switchTo', $teamB->id)
        ->assertRedirect('/teams/'.$teamB->id);

    expect($user->fresh()->current_team_id)->toBe($teamB->id);
});
