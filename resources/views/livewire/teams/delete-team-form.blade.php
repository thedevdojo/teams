<?php

use Devdojo\Teams\Actions\DeleteTeam;
use Devdojo\Teams\Models\Team;
use Livewire\Volt\Component;

new class extends Component
{
    public Team $team;

    public function mount(Team $team): void
    {
        $this->team = $team;
    }

    /**
     * Delete the team and move the user onto a remaining team.
     */
    public function delete(DeleteTeam $deleter): void
    {
        $deleter->delete(auth()->user(), $this->team);

        $user = auth()->user();
        $fallback = $user->personalTeam() ?? $user->allTeams()->first();

        if ($fallback) {
            $user->switchTeam($fallback);
        }

        $this->redirect($fallback ? url('/teams/'.$fallback->id) : url('/'), navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'canDelete' => auth()->user()->can('delete', $this->team) && ! $this->team->personal_team,
        ];
    }
}; ?>

<x-teams::card
    :title="__('Delete Team')"
    :description="__('Permanently delete this team.')"
    class="border-red-200! dark:border-red-900/40!"
>
    @if ($this->team->personal_team)
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('This is a personal team and cannot be deleted.') }}
        </p>
    @else
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Once a team is deleted, all of its resources and data will be permanently removed. This action cannot be undone.') }}
        </p>

        <x-teams::error :messages="$errors->getBag('deleteTeam')->get('team')" />

        @if ($canDelete)
            <div class="mt-5">
                <x-teams::button
                    variant="danger"
                    wire:click="delete"
                    wire:confirm="{{ __('Are you sure you want to delete this team? This cannot be undone.') }}"
                    wire:loading.attr="disabled"
                    wire:target="delete"
                >
                    {{ __('Delete Team') }}
                </x-teams::button>
            </div>
        @endif
    @endif
</x-teams::card>
