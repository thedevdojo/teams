<?php

namespace Devdojo\Teams;

use Devdojo\Teams\Listeners\CreatePersonalTeam;
use Devdojo\Teams\Policies\TeamPolicy;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Folio\Folio;
use Livewire\Volt\Volt;

class TeamsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/teams.php', 'teams');

        $this->app->singleton('devdojo.teams', fn () => new Teams);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'teams');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/teams.php' => config_path('teams.php'),
            ], 'teams:config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'teams:migrations');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/teams'),
            ], 'teams:views');
        }

        // Feature toggle — default-on when no foundation config is present, so the
        // package still works standalone.
        if (! config('foundation.features.teams', true)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Gate::policy(Teams::teamModel(), TeamPolicy::class);

        if (Teams::hasPersonalTeams()) {
            Event::listen(Registered::class, CreatePersonalTeam::class);
        }

        $this->registerFolioAndVolt();
    }

    /**
     * Register the bundled Folio pages and Volt components.
     */
    protected function registerFolioAndVolt(): void
    {
        $pages = __DIR__.'/../resources/views/pages';
        $components = __DIR__.'/../resources/views/livewire';

        if (class_exists(Folio::class) && File::exists($pages)) {
            // Folio already wraps its pages in the "web" group, so drop it here
            // to avoid running the session/cookie middleware twice.
            $pageMiddleware = array_values(array_diff(
                config('teams.middleware', ['web', 'auth']),
                ['web'],
            ));

            Folio::path($pages)->middleware(['*' => $pageMiddleware]);
        }

        // Only the embeddable components are Volt; the Folio pages above are plain
        // Blade that compose those components inside the package layout.
        if (class_exists(Volt::class) && File::exists($components)) {
            $this->app->booted(function () use ($components) {
                Volt::mount($components);
            });
        }
    }
}
