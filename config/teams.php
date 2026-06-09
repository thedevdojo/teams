<?php

use Devdojo\Teams\Models\Membership;
use Devdojo\Teams\Models\Team;
use Devdojo\Teams\Models\TeamInvitation;

return [

    /*
    |--------------------------------------------------------------------------
    | User model
    |--------------------------------------------------------------------------
    | The host application's User model. When null, the package falls back to
    | config('auth.providers.users.model'). Set explicitly if your User model
    | lives somewhere unusual.
    */

    'user_model' => env('TEAMS_USER_MODEL'),

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    | Swap any of these for your own subclass if you need to extend the team,
    | membership (the team_user pivot) or invitation models.
    */

    'models' => [
        'team' => Team::class,
        'membership' => Membership::class,
        'team_invitation' => TeamInvitation::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    | personal_teams  — auto-create a "personal" team for each user on
    |                   registration and set it as their current team.
    | invitations     — invite members by email (sends a signed accept link).
    |                   When false, members are added directly by their email
    |                   only if a matching user already exists.
    */

    'features' => [
        'personal_teams' => true,
        'invitations' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    | The middleware applied to the bundled team-management pages, the URL
    | prefix those Folio pages live under, and where to send the user after
    | creating or switching a team.
    */

    'middleware' => ['web', 'auth'],

    'prefix' => 'teams',

    'redirect_after_switch' => '/teams/{team}',

    'redirect_after_create' => '/teams/{team}',

    /*
    |--------------------------------------------------------------------------
    | Roles & permissions
    |--------------------------------------------------------------------------
    | Roles a member can hold on a team. The team *owner* implicitly has every
    | permission. Use these in your own app via
    | $user->hasTeamPermission($team, 'create'). Add, remove, or rename freely.
    |
    | The first role listed is treated as the default for new members.
    */

    'roles' => [

        'admin' => [
            'name' => 'Administrator',
            'description' => 'Administrators can perform any action.',
            'permissions' => ['create', 'read', 'update', 'delete'],
        ],

        'editor' => [
            'name' => 'Editor',
            'description' => 'Editors can read, create, and update.',
            'permissions' => ['read', 'create', 'update'],
        ],

        'member' => [
            'name' => 'Member',
            'description' => 'Members can read team resources.',
            'permissions' => ['read'],
        ],

    ],

];
