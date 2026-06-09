<?php

namespace Devdojo\Teams\Events;

use Devdojo\Teams\Models\Team;
use Illuminate\Foundation\Events\Dispatchable;

class TeamCreated
{
    use Dispatchable;

    public function __construct(public Team $team) {}
}
