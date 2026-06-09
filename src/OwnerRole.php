<?php

namespace Devdojo\Teams;

/**
 * The implicit role held by a team's owner. Owners are granted every
 * permission via the "*" wildcard, regardless of the configured roles.
 */
class OwnerRole extends Role
{
    public function __construct()
    {
        parent::__construct(
            key: 'owner',
            name: 'Owner',
            permissions: ['*'],
            description: 'The team owner has full access to the team.',
        );
    }
}
