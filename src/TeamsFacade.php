<?php

namespace Devdojo\Teams;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string userModel()
 * @method static string teamModel()
 * @method static \Devdojo\Teams\Models\Team newTeam()
 * @method static string membershipModel()
 * @method static string teamInvitationModel()
 * @method static \Devdojo\Teams\Models\TeamInvitation newTeamInvitation()
 * @method static bool hasPersonalTeams()
 * @method static bool sendsInvitations()
 * @method static \Devdojo\Teams\Role role(string $key, string $name, array $permissions, string $description = '')
 * @method static array<string, \Devdojo\Teams\Role> roles()
 * @method static \Devdojo\Teams\Role|null findRole(?string $key)
 * @method static \Devdojo\Teams\Role|null defaultRole()
 * @method static array<int, string> permissions()
 *
 * @see Teams
 */
class TeamsFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'devdojo.teams';
    }
}
