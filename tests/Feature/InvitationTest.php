<?php

use Devdojo\Teams\Actions\InviteTeamMember;
use Devdojo\Teams\Events\TeamMemberAdded;
use Devdojo\Teams\Events\TeamMemberInvited;
use Devdojo\Teams\Mail\TeamInvitationMail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

it('issues an invitation and emails a signed accept link', function () {
    Mail::fake();
    Event::fake([TeamMemberInvited::class]);

    $owner = createUser();
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);

    $invitation = app(InviteTeamMember::class)->invite($owner, $team, 'invitee@example.com', 'editor');

    expect($invitation->email)->toBe('invitee@example.com')
        ->and($invitation->role)->toBe('editor')
        ->and($invitation->team->is($team))->toBeTrue()
        ->and($team->hasPendingInvitation('invitee@example.com'))->toBeTrue();

    Event::assertDispatched(TeamMemberInvited::class, fn ($event) => $event->invitation->is($invitation));
    Mail::assertSent(TeamInvitationMail::class, fn ($mail) => $mail->hasTo('invitee@example.com'));
});

it('rejects inviting someone who already belongs to the team', function () {
    Mail::fake();

    $owner = createUser();
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);

    expect(fn () => app(InviteTeamMember::class)->invite($owner, $team, $owner->email, 'member'))
        ->toThrow(ValidationException::class);

    Mail::assertNothingSent();
});

it('rejects inviting the same email twice', function () {
    Mail::fake();

    $owner = createUser();
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);

    app(InviteTeamMember::class)->invite($owner, $team, 'twice@example.com', 'member');

    expect(fn () => app(InviteTeamMember::class)->invite($owner, $team->fresh(), 'twice@example.com', 'member'))
        ->toThrow(ValidationException::class);
});

it('renders the invitation mail with the signed accept url', function () {
    $owner = createUser();
    $team = $owner->ownedTeams()->create(['name' => 'Mail Team', 'personal_team' => false]);
    $invitation = $team->teamInvitations()->create(['email' => 'invitee@example.com', 'role' => 'member']);

    $mail = new TeamInvitationMail($invitation);

    expect($mail->envelope()->subject)->toContain('Mail Team');

    $rendered = $mail->render();

    expect($rendered)->toContain('team-invitations/'.$invitation->id.'/accept')
        ->and($rendered)->toContain('signature=');
});

it('accepts an invitation from its signed link and joins the team', function () {
    Event::fake([TeamMemberAdded::class]);

    $owner = createUser();
    $invitee = createUser(['email' => 'joiner@example.com']);
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);
    $invitation = $team->teamInvitations()->create(['email' => 'joiner@example.com', 'role' => 'editor']);

    $url = URL::signedRoute('teams.invitations.accept', ['invitation' => $invitation->id]);

    $this->actingAs($invitee)
        ->get($url)
        ->assertRedirect('/teams/'.$team->id)
        ->assertSessionHas('status');

    expect($team->fresh()->hasUser($invitee))->toBeTrue()
        ->and($team->users()->first()->membership->role)->toBe('editor')
        ->and($invitee->fresh()->current_team_id)->toBe($team->id)
        ->and($invitation->fresh())->toBeNull();

    Event::assertDispatched(TeamMemberAdded::class);
});

it('forbids accepting an invitation sent to a different email', function () {
    $owner = createUser();
    $impostor = createUser(['email' => 'impostor@example.com']);
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);
    $invitation = $team->teamInvitations()->create(['email' => 'intended@example.com', 'role' => 'member']);

    $url = URL::signedRoute('teams.invitations.accept', ['invitation' => $invitation->id]);

    $this->actingAs($impostor)->get($url)->assertForbidden();

    expect($team->fresh()->users()->count())->toBe(0)
        ->and($invitation->fresh())->not->toBeNull();
});

it('rejects an accept link with an invalid signature', function () {
    $owner = createUser();
    $invitee = createUser(['email' => 'tamper@example.com']);
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);
    $invitation = $team->teamInvitations()->create(['email' => 'tamper@example.com', 'role' => 'member']);

    $url = route('teams.invitations.accept', ['invitation' => $invitation->id]).'?signature=forged';

    $this->actingAs($invitee)->get($url)->assertForbidden();

    expect($team->fresh()->users()->count())->toBe(0);
});

it('cancels a pending invitation by deleting it', function () {
    $owner = createUser();
    $team = $owner->ownedTeams()->create(['name' => 'Acme', 'personal_team' => false]);
    $invitation = $team->teamInvitations()->create(['email' => 'cancel@example.com', 'role' => 'member']);

    expect($team->hasPendingInvitation('cancel@example.com'))->toBeTrue();

    $invitation->delete();

    expect($team->fresh()->hasPendingInvitation('cancel@example.com'))->toBeFalse();
});
