<?php

use Devdojo\Teams\Actions\UpdateTeamName;
use Devdojo\Teams\Models\Team;
use Livewire\Volt\Component;

new class extends Component
{
    public Team $team;

    public string $name = '';

    public function mount(Team $team): void
    {
        $this->team = $team;
        $this->name = $team->name;
    }

    /**
     * Persist the team's new name.
     */
    public function save(UpdateTeamName $updater): void
    {
        $updater->update(auth()->user(), $this->team, ['name' => $this->name]);

        $this->team->refresh();
        $this->dispatch('team-name-saved');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'canUpdate' => auth()->user()->can('update', $this->team),
        ];
    }
}; ?>

<x-teams::card :title="__('Team Name')" :description="__('Update the name your team is known by.')">
    <form wire:submit="save" class="space-y-5">
        <div>
            <x-teams::label for="team-name">{{ __('Team Name') }}</x-teams::label>
            <x-teams::input id="team-name" wire:model="name" class="mt-1.5" :disabled="! $canUpdate" />
            <x-teams::error :messages="$errors->get('name')" />
        </div>

        @if ($canUpdate)
            <div class="flex items-center justify-end gap-3">
                <span
                    x-data="{ shown: false }"
                    @team-name-saved.window="shown = true; setTimeout(() => shown = false, 2500)"
                    x-show="shown"
                    x-transition
                    x-cloak
                    class="text-sm text-zinc-500 dark:text-zinc-400"
                >{{ __('Saved.') }}</span>

                <x-teams::button type="submit" wire:loading.attr="disabled" wire:target="save">
                    {{ __('Save') }}
                </x-teams::button>
            </div>
        @endif
    </form>
</x-teams::card>
