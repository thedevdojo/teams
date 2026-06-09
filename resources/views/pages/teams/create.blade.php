<?php

use function Laravel\Folio\middleware;
use function Laravel\Folio\name;

middleware(['auth']);
name('teams.create');

?>

<x-teams::layouts.app :title="__('Create Team')">
    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ __('Create Team') }}</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Spin up a new team to collaborate with others.') }}</p>
        </div>

        <livewire:teams.create-team-form />
    </div>
</x-teams::layouts.app>
