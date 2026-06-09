<?php

use Devdojo\Teams\Actions\CreateTeam;
use Devdojo\Teams\Support\Redirects;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';

    /**
     * Create the team and redirect onto it.
     */
    public function create(CreateTeam $creator): void
    {
        $team = $creator->create(auth()->user(), [
            'name' => $this->name,
            'personal_team' => false,
        ]);

        $this->redirect(Redirects::afterCreate($team), navigate: true);
    }
}; ?>

<x-teams::card :title="__('Create Team')" :description="__('Teams let you collaborate with others on shared resources.')">
    <form wire:submit="create" class="space-y-5">
        <div>
            <x-teams::label for="create-team-name">{{ __('Team Name') }}</x-teams::label>
            <x-teams::input id="create-team-name" wire:model="name" class="mt-1.5" autofocus />
            <x-teams::error :messages="$errors->get('name')" />
        </div>

        <div class="flex justify-end">
            <x-teams::button type="submit" wire:loading.attr="disabled" wire:target="create">
                <span wire:loading.remove wire:target="create">{{ __('Create Team') }}</span>
                <span wire:loading wire:target="create">{{ __('Creating…') }}</span>
            </x-teams::button>
        </div>
    </form>
</x-teams::card>
