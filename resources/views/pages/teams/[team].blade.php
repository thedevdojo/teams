<?php

use Devdojo\Teams\Teams;
use Illuminate\Support\Facades\Gate;

use function Laravel\Folio\middleware;
use function Laravel\Folio\name;

middleware(['auth']);
name('teams.show');

$team = Teams::teamModel()::findOrFail($team);

Gate::authorize('view', $team);

?>

<x-teams::layouts.app :title="$team->name">
    <div class="mx-auto max-w-3xl space-y-8">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $team->name }}</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Owned by :owner', ['owner' => $team->owner?->name ?? __('Unknown')]) }}
                </p>
            </div>

            @if ($team->personal_team)
                <span class="inline-flex items-center rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ __('Personal Team') }}</span>
            @endif
        </div>

        <livewire:teams.update-team-name-form :team="$team" wire:key="update-name-{{ $team->id }}" />

        <livewire:teams.team-member-manager :team="$team" wire:key="member-manager-{{ $team->id }}" />

        <livewire:teams.delete-team-form :team="$team" wire:key="delete-team-{{ $team->id }}" />
    </div>
</x-teams::layouts.app>
