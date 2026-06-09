<?php

namespace Devdojo\Teams\Support;

use Devdojo\Teams\Models\Team;

class Redirects
{
    /**
     * Where to send the user after switching to (or joining) a team.
     */
    public static function afterSwitch(Team $team): string
    {
        return static::resolve(config('teams.redirect_after_switch', '/'), $team);
    }

    /**
     * Where to send the user after creating a team.
     */
    public static function afterCreate(Team $team): string
    {
        return static::resolve(config('teams.redirect_after_create', '/'), $team);
    }

    /**
     * Replace the {team} placeholder in a configured redirect path.
     */
    protected static function resolve(string $pattern, Team $team): string
    {
        return str_replace('{team}', (string) $team->getKey(), $pattern);
    }
}
