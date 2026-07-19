<?php

it('renders the create-team page for an authenticated user', function () {
    $this->actingAs(createUser());

    $this->get('/teams/create')
        ->assertOk()
        ->assertSee('Create Team');
});

it('renders the team settings page for the owner', function () {
    $owner = createUser();
    $team = $owner->ownedTeams()->create(['name' => 'Visible Team', 'personal_team' => false]);

    $this->actingAs($owner);

    $this->get('/teams/'.$team->id)
        ->assertOk()
        ->assertSee('Visible Team');
});

it('forbids the team settings page for users outside the team', function () {
    $owner = createUser();
    $outsider = createUser();
    $team = $owner->ownedTeams()->create(['name' => 'Hidden Team', 'personal_team' => false]);

    $this->actingAs($outsider);

    $this->get('/teams/'.$team->id)->assertForbidden();
});
