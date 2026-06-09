<?php

use Devdojo\Teams\Support\Redirects;
use Devdojo\Teams\Teams;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Switch the authenticated user onto the given team.
     */
    public function switchTo(int $teamId): void
    {
        $team = Teams::teamModel()::findOrFail($teamId);

        if (auth()->user()->switchTeam($team)) {
            $this->redirect(Redirects::afterSwitch($team), navigate: true);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $user = auth()->user();

        return [
            'current' => $user->currentTeamOrDefault(),
            'teams' => $user->allTeams(),
        ];
    }
}; ?>

<div x-data="{ open: false }" @click.outside="open = false" class="relative">
    <button
        type="button"
        @click="open = ! open"
        class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700"
    >
        <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
        <span class="max-w-[10rem] truncate">{{ $current?->name ?? __('No Team') }}</span>
        <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.origin.top.right
        class="absolute right-0 z-50 mt-2 w-60 origin-top-right overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
    >
        <div class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Switch Teams') }}</div>

        <div class="max-h-64 overflow-y-auto py-1">
            @foreach ($teams as $team)
                <button
                    wire:key="switch-team-{{ $team->id }}"
                    wire:click="switchTo({{ $team->id }})"
                    class="flex w-full items-center justify-between gap-2 px-4 py-2 text-left text-sm text-zinc-700 transition hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-zinc-700/60"
                >
                    <span class="truncate">{{ $team->name }}</span>
                    @if (auth()->user()->isCurrentTeam($team))
                        <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="border-t border-zinc-100 py-1 dark:border-zinc-700">
            @if ($current)
                <a href="{{ url('/teams/'.$current->id) }}" class="block px-4 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-zinc-700/60">{{ __('Team Settings') }}</a>
            @endif
            <a href="{{ url('/teams/create') }}" class="block px-4 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-zinc-700/60">{{ __('Create New Team') }}</a>
        </div>
    </div>
</div>
