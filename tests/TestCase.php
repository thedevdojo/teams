<?php

namespace Devdojo\Teams\Tests;

use Devdojo\Teams\Teams;
use Devdojo\Teams\TeamsServiceProvider;
use Devdojo\Teams\Tests\Models\User;
use Laravel\Folio\FolioServiceProvider;
use Livewire\LivewireServiceProvider;
use Livewire\Volt\VoltServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            VoltServiceProvider::class,
            FolioServiceProvider::class,
            TeamsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Point the package at the test User model (HasTeams trait applied).
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $paths = [
            // Host users table (test stub) — must exist before the package's
            // add_current_team_id_to_users_table migration runs.
            __DIR__.'/database/migrations',
            // Package teams migrations.
            __DIR__.'/../database/migrations',
        ];

        foreach (array_filter($paths, 'is_dir') as $path) {
            $this->loadMigrationsFrom($path);
        }
    }

    protected function tearDown(): void
    {
        // Runtime-registered roles are static; don't leak them across tests.
        Teams::flushRoles();

        parent::tearDown();
    }
}
