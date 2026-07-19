<?php

namespace Devdojo\Teams\Tests\Models;

use Devdojo\Teams\Traits\HasTeams;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Minimal host User stub used to prove the teams trait works against an
 * application that provides its own User model.
 */
class User extends Authenticatable
{
    use HasTeams;

    protected $table = 'users';

    protected $guarded = [];
}
