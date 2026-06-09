<?php

namespace Devdojo\Teams\Events;

use Devdojo\Teams\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class TeamMemberAdded
{
    use Dispatchable;

    public function __construct(
        public Team $team,
        public Model $user,
    ) {}
}
