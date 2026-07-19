<?php

use Devdojo\Teams\Tests\Models\User;
use Devdojo\Teams\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

function createUser(array $attributes = []): User
{
    static $sequence = 0;

    $sequence++;

    return User::create(array_merge([
        'name' => 'User '.$sequence,
        'email' => 'user'.$sequence.'@example.com',
        'password' => bcrypt('password'),
    ], $attributes));
}
