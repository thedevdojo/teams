<?php

namespace Devdojo\Teams\Events;

use Devdojo\Teams\Models\TeamInvitation;
use Illuminate\Foundation\Events\Dispatchable;

class TeamMemberInvited
{
    use Dispatchable;

    public function __construct(public TeamInvitation $invitation) {}
}
