<?php

namespace Devdojo\Teams;

use Devdojo\Teams\Models\Membership;
use Devdojo\Teams\Models\Team;
use Devdojo\Teams\Models\TeamInvitation;
use Illuminate\Database\Eloquent\Model;

class Teams
{
    /**
     * Roles registered at runtime (override or augment the config roles).
     *
     * @var array<string, Role>
     */
    protected static array $customRoles = [];

    /**
     * Resolve the host application's User model class.
     *
     * @return class-string<Model>
     */
    public static function userModel(): string
    {
        return config('teams.user_model')
            ?? config('auth.providers.users.model')
            ?? 'App\\Models\\User';
    }

    public static function newUserModel(): Model
    {
        $model = static::userModel();

        return new $model;
    }

    /**
     * @return class-string<Team>
     */
    public static function teamModel(): string
    {
        return config('teams.models.team', Team::class);
    }

    public static function newTeam(): Team
    {
        $model = static::teamModel();

        return new $model;
    }

    /**
     * @return class-string<Membership>
     */
    public static function membershipModel(): string
    {
        return config('teams.models.membership', Membership::class);
    }

    /**
     * @return class-string<TeamInvitation>
     */
    public static function teamInvitationModel(): string
    {
        return config('teams.models.team_invitation', TeamInvitation::class);
    }

    public static function newTeamInvitation(): TeamInvitation
    {
        $model = static::teamInvitationModel();

        return new $model;
    }

    /**
     * Whether each user should get an auto-created personal team.
     */
    public static function hasPersonalTeams(): bool
    {
        return (bool) config('teams.features.personal_teams', true);
    }

    /**
     * Whether members are invited by email (vs. added directly).
     */
    public static function sendsInvitations(): bool
    {
        return (bool) config('teams.features.invitations', true);
    }

    /**
     * Register (or override) a role at runtime.
     *
     * @param  array<int, string>  $permissions
     */
    public static function role(string $key, string $name, array $permissions, string $description = ''): Role
    {
        return static::$customRoles[$key] = new Role($key, $name, $permissions, $description);
    }

    /**
     * All available roles, keyed by their identifier.
     *
     * @return array<string, Role>
     */
    public static function roles(): array
    {
        $roles = [];

        foreach (config('teams.roles', []) as $key => $role) {
            $roles[$key] = new Role(
                key: $key,
                name: $role['name'] ?? ucfirst($key),
                permissions: $role['permissions'] ?? [],
                description: $role['description'] ?? '',
            );
        }

        return array_merge($roles, static::$customRoles);
    }

    /**
     * Find a role by its key.
     */
    public static function findRole(?string $key): ?Role
    {
        if ($key === null) {
            return null;
        }

        return static::roles()[$key] ?? null;
    }

    /**
     * The default role assigned to a new member (the first configured role).
     */
    public static function defaultRole(): ?Role
    {
        $roles = static::roles();

        return $roles[array_key_first($roles)] ?? null;
    }

    /**
     * All distinct permission strings across every role.
     *
     * @return array<int, string>
     */
    public static function permissions(): array
    {
        return collect(static::roles())
            ->flatMap(fn (Role $role) => $role->permissions)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Forget any runtime-registered roles (primarily useful in tests).
     */
    public static function flushRoles(): void
    {
        static::$customRoles = [];
    }
}
