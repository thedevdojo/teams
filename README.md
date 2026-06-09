# DevDojo Teams

Drop-in **team support** for Laravel — teams, memberships, roles & permissions, and email
invitations — with a polished, ready-to-use **Livewire / Volt + Tailwind** UI. The data model
and API mirror the team functionality in the official Laravel starter kits (Jetstream), so it
will feel immediately familiar.

`devdojo/teams` is one of the feature packages bundled by
[`devdojo/foundation`](https://github.com/thedevdojo/foundation), but it works perfectly well
**standalone** in any Laravel app.

---

## Table of contents

- [Requirements](#requirements)
- [How it works](#how-it-works)
- [Installation](#installation)
- [Wiring your User model](#wiring-your-user-model)
- [Personal teams](#personal-teams)
- [The data model](#the-data-model)
- [The bundled UI](#the-bundled-ui)
  - [Add the team switcher to your layout](#add-the-team-switcher-to-your-layout)
  - [Embedding the management components](#embedding-the-management-components)
  - [Tailwind note](#tailwind-note)
- [Working with teams in code](#working-with-teams-in-code)
- [Roles & permissions](#roles--permissions)
- [Inviting members](#inviting-members)
- [Switching teams](#switching-teams)
- [Actions](#actions)
- [Events](#events)
- [Authorization (the Team policy)](#authorization-the-team-policy)
- [Using with DevDojo Foundation](#using-with-devdojo-foundation)
- [Configuration reference](#configuration-reference)
- [FAQ / troubleshooting](#faq--troubleshooting)
- [License](#license)

---

## Requirements

| Requirement | Notes |
| --- | --- |
| PHP `^8.2` | |
| Laravel `^11 / ^12 / ^13` | |
| Livewire `^3 / ^4` + Volt `^1` | Powers the bundled UI components & pages. |
| Laravel Folio `^1` | Routes the bundled `/teams/*` pages. |

The UI uses **Tailwind CSS** utility classes. A working mailer is needed only if you use email
[invitations](#inviting-members).

---

## How it works

```
┌──────────────────────────────────────────────────────────────────────┐
│ devdojo/teams                                                          │
│                                                                        │
│   User ──owns──▶  Team ──hasMany──▶ TeamInvitation                     │
│     │              ▲                                                    │
│     └──belongsTo──┘ (team_user pivot = Membership, carries `role`)     │
│                                                                        │
│   Trait added to your User:                                            │
│     • HasTeams  (teams, ownedTeams, switchTeam, hasTeamPermission …)   │
│                                                                        │
│   UI:  /teams/create  •  /teams/{team}  •  <livewire:teams.* />        │
│   Roles & permissions  •  email invitations  •  Team policy           │
└──────────────────────────────────────────────────────────────────────┘
```

- A **Team** is owned by one user (`teams.user_id`) and has many members through the
  `team_user` pivot.
- Each membership carries a **role** (`admin`, `editor`, `member`, … — fully configurable).
  The **owner** implicitly has every permission.
- A user's **current team** is stored on `users.current_team_id`; `switchTeam()` changes it.
- Members can be added directly or **invited by email** with a signed accept link.

---

## Installation

```bash
composer require devdojo/teams
```

Publish the config and migrations, then migrate:

```bash
php artisan vendor:publish --tag=teams:config
php artisan vendor:publish --tag=teams:migrations
php artisan migrate
```

This creates the `teams`, `team_user`, and `team_invitations` tables and adds a nullable
`current_team_id` column to your `users` table.

> Migrations are **publish-only** (not auto-loaded) so the tables live in your app's
> `database/migrations` and are yours to edit.

If your `User` model can't be discovered from `config('auth.providers.users.model')`, set it
explicitly:

```env
TEAMS_USER_MODEL="App\\Models\\User"
```

Then [wire your User model](#wiring-your-user-model).

---

## Wiring your User model

Add the `HasTeams` trait to your `User` model:

```php
use Devdojo\Teams\Traits\HasTeams;

class User extends Authenticatable
{
    use HasTeams;
}
```

That's the only required integration step. The trait adds the relationships and helper methods
described in [Working with teams in code](#working-with-teams-in-code).

---

## Personal teams

By default every newly **registered** user is given a personal team, which becomes their
current team. This is handled automatically by listening for Laravel's `Registered` event — no
code required, as long as your `User` uses the `HasTeams` trait.

Turn it off in `config/teams.php`:

```php
'features' => [
    'personal_teams' => false,
],
```

You can also create a team yourself at any time:

```php
use Devdojo\Teams\Actions\CreateTeam;

$team = app(CreateTeam::class)->create($user, ['name' => 'Acme']);
```

---

## The data model

### `teams`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | id | |
| `user_id` | foreignId | the **owner** (indexed) |
| `name` | string | |
| `personal_team` | boolean | personal teams cannot be deleted |
| timestamps | | |

### `team_user` (membership pivot → `Membership`)

| Column | Type | Notes |
| --- | --- | --- |
| `id` | id | |
| `team_id` | foreignId | |
| `user_id` | foreignId | |
| `role` | string, nullable | role key, e.g. `admin` |
| timestamps | | unique on `(team_id, user_id)` |

### `team_invitations`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | id | |
| `team_id` | foreignId | FK → `teams` (`cascade`) |
| `email` | string | invited address |
| `role` | string, nullable | role to grant on accept |
| timestamps | | unique on `(team_id, email)` |

### `users` (added column)

| Column | Type | Notes |
| --- | --- | --- |
| `current_team_id` | unsigned big int, nullable | the team the user is viewing |

---

## The bundled UI

Two Folio pages are registered out of the box (behind your configured middleware, default
`auth`):

| URL | Route name | Purpose |
| --- | --- | --- |
| `/teams/create` | `teams.create` | Create a new team |
| `/teams/{team}` | `teams.show` | Team settings: name, members, invitations, delete |

These render inside a **self-contained layout** (`x-teams::layouts.app`) so they work with zero
setup. To match your own app chrome instead, drop the [embeddable components](#embedding-the-management-components)
into your own pages — that's the recommended approach for production.

### Add the team switcher to your layout

The switcher lists the user's teams, shows the current one, and links to create/manage:

```blade
@auth
    <livewire:teams.team-switcher />
@endauth
```

### Embedding the management components

Every piece of the management screen is an independent Livewire/Volt component you can place in
your **own** Blade layout:

```blade
{{-- Your own settings page, using your own layout --}}
<livewire:teams.update-team-name-form :team="$team" />
<livewire:teams.team-member-manager   :team="$team" />
<livewire:teams.delete-team-form      :team="$team" />

{{-- Standalone --}}
<livewire:teams.create-team-form />
```

| Component | What it does |
| --- | --- |
| `teams.team-switcher` | Current-team dropdown + switch / create / settings links |
| `teams.create-team-form` | Create a team and switch onto it |
| `teams.update-team-name-form` | Rename the team (owner / `update` permission) |
| `teams.team-member-manager` | Invite/add members, manage roles, cancel invites, remove / leave |
| `teams.delete-team-form` | Delete a non-personal team (owner only) |

### Tailwind note

The bundled views use Tailwind utility classes. When a Vite build exists, the standalone pages
use your compiled CSS; otherwise they fall back to the Tailwind CDN so they still render.

For production, tell Tailwind to scan the package so its classes are generated. In Tailwind v4,
add a `@source` line to your `resources/css/app.css`:

```css
@import "tailwindcss";

/* installed via Composer */
@source "../../vendor/devdojo/teams/resources/**/*.blade.php";
```

Or publish the views and own them entirely:

```bash
php artisan vendor:publish --tag=teams:views   # → resources/views/vendor/teams
```

---

## Working with teams in code

The `HasTeams` trait gives your `User` model:

```php
$user->currentTeam;                 // BelongsTo — the user's current team
$user->currentTeamOrDefault();      // current team, falling back to personal/first
$user->ownedTeams;                  // HasMany — teams the user owns
$user->teams;                       // BelongsToMany — teams they belong to
$user->allTeams();                  // Collection — owned + member teams
$user->personalTeam();              // ?Team

$user->ownsTeam($team);             // bool
$user->belongsToTeam($team);        // bool — owner or member
$user->isCurrentTeam($team);        // bool

$user->teamRole($team);             // ?Role — OwnerRole for the owner
$user->hasTeamRole($team, 'admin'); // bool
$user->teamPermissions($team);      // array<string>
$user->hasTeamPermission($team, 'update'); // bool

$user->switchTeam($team);           // bool — sets current_team_id
```

On a `Team`:

```php
$team->owner;                       // BelongsTo User
$team->users;                       // BelongsToMany members (pivot: $member->membership->role)
$team->allUsers();                  // owner + members
$team->teamInvitations;             // HasMany pending invitations
$team->hasUser($user);              // bool
$team->hasUserWithEmail($email);    // bool
$team->removeUser($user);           // detach + reset their current team if needed
$team->purge();                     // delete members, invitations, and the team
```

---

## Roles & permissions

Roles are defined in `config/teams.php`. The **first** role listed is the default for new
members; the team owner always has every permission.

```php
'roles' => [
    'admin'  => ['name' => 'Administrator', 'permissions' => ['create', 'read', 'update', 'delete']],
    'editor' => ['name' => 'Editor',        'permissions' => ['read', 'create', 'update']],
    'member' => ['name' => 'Member',         'permissions' => ['read']],
],
```

Check permissions anywhere in your app to authorize **your own** resources:

```php
if ($user->hasTeamPermission($user->currentTeam, 'update')) {
    // allow editing a team-owned resource
}
```

You may also register or override roles at runtime (e.g. in a service provider) via the
`Teams` facade:

```php
use Teams; // alias for Devdojo\Teams\Teams

Teams::role('billing', 'Billing Manager', ['read', 'update'], 'Manages the team subscription.');
```

---

## Inviting members

With `features.invitations` **enabled** (the default), adding a member creates a
`team_invitations` row and emails a **signed** accept link:

| Method | URI | Name | Middleware |
| --- | --- | --- | --- |
| `GET` | `/team-invitations/{invitation}/accept` | `teams.invitations.accept` | `web`, `auth`, `signed` |

When the recipient clicks the link (and is logged in with the matching email), they're added to
the team and switched onto it. The invitation email is a Markdown mailable — make sure your
app's mailer is configured.

With invitations **disabled**, the member manager adds existing users directly by email (no mail
is sent, and the email must already belong to a registered user):

```php
'features' => [
    'invitations' => false,
],
```

---

## Switching teams

```php
$user->switchTeam($team);   // returns false if the user doesn't belong to the team
```

The bundled `teams.team-switcher` component does this for you and redirects to
`config('teams.redirect_after_switch')` (the `{team}` placeholder is replaced with the team id).

---

## Actions

All write operations live in single-purpose, injectable action classes. They each **validate**
and **authorize**, then fire the relevant [event](#events). Call them from your own controllers,
jobs, or commands:

```php
use Devdojo\Teams\Actions\CreateTeam;
use Devdojo\Teams\Actions\UpdateTeamName;
use Devdojo\Teams\Actions\AddTeamMember;
use Devdojo\Teams\Actions\InviteTeamMember;
use Devdojo\Teams\Actions\UpdateTeamMemberRole;
use Devdojo\Teams\Actions\RemoveTeamMember;
use Devdojo\Teams\Actions\DeleteTeam;

app(CreateTeam::class)->create($user, ['name' => 'Acme']);
app(UpdateTeamName::class)->update($user, $team, ['name' => 'Acme Inc.']);
app(AddTeamMember::class)->add($user, $team, 'jane@example.com', 'editor');
app(InviteTeamMember::class)->invite($user, $team, 'jane@example.com', 'editor');
app(UpdateTeamMemberRole::class)->update($user, $team, $memberId, 'admin');
app(RemoveTeamMember::class)->remove($user, $team, $member);
app(DeleteTeam::class)->delete($user, $team);
```

---

## Events

| Event | Dispatched when |
| --- | --- |
| `TeamCreated` | a team is created |
| `TeamUpdated` | a team's name is updated |
| `TeamDeleted` | a team is deleted |
| `TeamMemberAdded` | a user is added / accepts an invite |
| `TeamMemberInvited` | a user is invited by email |
| `TeamMemberRemoved` | a member is removed or leaves |

```php
use Devdojo\Teams\Events\TeamMemberAdded;

Event::listen(TeamMemberAdded::class, function (TeamMemberAdded $event) {
    // $event->team, $event->user
});
```

---

## Authorization (the Team policy)

A `TeamPolicy` is registered automatically, so `$user->can('update', $team)` and friends work
everywhere (Blade `@can`, controllers, the actions above):

| Ability | Default rule |
| --- | --- |
| `view` | member or owner |
| `create` | any authenticated user |
| `update` | owner or `update` permission |
| `addTeamMember` | owner or `create` permission |
| `updateTeamMember` | owner or `update` permission |
| `removeTeamMember` | owner or `delete` permission |
| `delete` | owner only |

Publish the policy or point the `teams.models.team` config at your own subclass to customize.

---

## Using with DevDojo Foundation

When the [`devdojo/foundation`](https://github.com/thedevdojo/foundation) metapackage is
installed, teams **self-gates** on its feature flag:

```php
// config/foundation.php
'features' => [
    'teams' => true,   // flip to false (or toggle at /foundation/setup) to disable
],
'depends' => [
    'teams' => ['auth'], // enabling teams ensures auth is enabled too
],
```

When `teams` is disabled, the routes, Folio pages, Volt components, policy, and the personal-team
listener are not registered. The models, trait, and migrations remain available, so toggling is
lossless. Standalone (no Foundation present), the flag is absent and teams defaults to **on**.

---

## Configuration reference

### `config/teams.php`

| Key | Default | Purpose |
| --- | --- | --- |
| `user_model` | `env('TEAMS_USER_MODEL')` | Host User model (null → `auth.providers.users.model`) |
| `models.team` | `Devdojo\Teams\Models\Team` | Swap for a subclass to extend |
| `models.membership` | `Devdojo\Teams\Models\Membership` | The `team_user` pivot model |
| `models.team_invitation` | `Devdojo\Teams\Models\TeamInvitation` | |
| `features.personal_teams` | `true` | Auto-create a personal team on registration |
| `features.invitations` | `true` | Invite by email vs. add existing users directly |
| `middleware` | `['web', 'auth']` | Middleware for the bundled pages |
| `prefix` | `teams` | URL prefix the pages live under |
| `redirect_after_switch` | `/teams/{team}` | Redirect after switching/joining (`{team}` → id) |
| `redirect_after_create` | `/teams/{team}` | Redirect after creating a team |
| `roles` | admin / editor / member | Available roles & their permissions |

### Publish tags

| Tag | Publishes to |
| --- | --- |
| `teams:config` | `config/teams.php` |
| `teams:migrations` | `database/migrations` |
| `teams:views` | `resources/views/vendor/teams` |

---

## FAQ / troubleshooting

**The bundled pages look unstyled.**
Tailwind isn't generating the package's classes. Add the `@source` directive shown in the
[Tailwind note](#tailwind-note), rebuild your CSS, or publish the views and integrate them with
your own layout.

**No personal team is being created on registration.**
Ensure `features.personal_teams` is `true`, your `User` uses the `HasTeams` trait, and the
`Illuminate\Auth\Events\Registered` event actually fires during your registration flow.

**Invitation emails aren't sending.**
Email invitations require a configured mailer. Set `features.invitations => false` to add
existing users directly instead (no mail needed).

**`current_team_id` is null.**
Call `$user->switchTeam($team)` (the switcher and create form do this for you), or rely on
`$user->currentTeamOrDefault()` which falls back to the personal/first team.

**Where do the tables come from?**
They're publish-only migrations — run
`php artisan vendor:publish --tag=teams:migrations && php artisan migrate`.

---

## License

MIT © [DevDojo](https://devdojo.com)
# teams
