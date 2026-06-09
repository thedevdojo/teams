<?php

namespace Devdojo\Teams\Models;

use Devdojo\Teams\Role;
use Devdojo\Teams\Teams;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * The pivot model for the team_user table. Carries the member's role.
 *
 * @property string|null $role
 */
class Membership extends Pivot
{
    protected $table = 'team_user';

    public $incrementing = true;

    /**
     * Resolve the member's role as a Role value object.
     */
    public function role(): ?Role
    {
        return Teams::findRole($this->getAttribute('role'));
    }
}
