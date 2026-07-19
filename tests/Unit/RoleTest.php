<?php

use Devdojo\Teams\OwnerRole;
use Devdojo\Teams\Role;

it('grants only its listed permissions', function () {
    $role = new Role('editor', 'Editor', ['read', 'create', 'update']);

    expect($role->hasPermission('read'))->toBeTrue()
        ->and($role->hasPermission('update'))->toBeTrue()
        ->and($role->hasPermission('delete'))->toBeFalse();
});

it('grants every permission through the wildcard', function () {
    $role = new Role('super', 'Super', ['*']);

    expect($role->hasPermission('anything-at-all'))->toBeTrue();
});

it('serializes to an array and json', function () {
    $role = new Role('member', 'Member', ['read'], 'Read-only access.');

    $expected = [
        'key' => 'member',
        'name' => 'Member',
        'description' => 'Read-only access.',
        'permissions' => ['read'],
    ];

    expect($role->toArray())->toBe($expected)
        ->and($role->jsonSerialize())->toBe($expected);
});

it('sets the description fluently', function () {
    $role = (new Role('member', 'Member'))->description('Updated description');

    expect($role)->toBeInstanceOf(Role::class)
        ->and($role->description)->toBe('Updated description');
});

it('gives the owner role the wildcard permission', function () {
    $owner = new OwnerRole;

    expect($owner->key)->toBe('owner')
        ->and($owner->permissions)->toBe(['*'])
        ->and($owner->hasPermission('delete'))->toBeTrue();
});
