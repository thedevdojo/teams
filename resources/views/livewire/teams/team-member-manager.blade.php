<?php

use Devdojo\Teams\Actions\AddTeamMember;
use Devdojo\Teams\Actions\InviteTeamMember;
use Devdojo\Teams\Actions\RemoveTeamMember;
use Devdojo\Teams\Actions\UpdateTeamMemberRole;
use Devdojo\Teams\Models\Team;
use Devdojo\Teams\Teams;
use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;

new class extends Component
{
    public Team $team;

    public string $email = '';

    public string $role = '';

    public function mount(Team $team): void
    {
        $this->team = $team;
        $this->role = (string) array_key_first(Teams::roles());
    }

    /**
     * Invite (or directly add) a member to the team.
     */
    public function addTeamMember(): void
    {
        $user = auth()->user();

        if (Teams::sendsInvitations()) {
            app(InviteTeamMember::class)->invite($user, $this->team, $this->email, $this->role);
        } else {
            app(AddTeamMember::class)->add($user, $this->team, $this->email, $this->role);
        }

        $this->reset('email');
        $this->team->refresh();
        $this->dispatch('member-added');
    }

    /**
     * Change an existing member's role.
     */
    public function updateRole(int $userId, string $role): void
    {
        app(UpdateTeamMemberRole::class)->update(auth()->user(), $this->team, $userId, $role);

        $this->team->refresh();
    }

    /**
     * Cancel a pending invitation.
     */
    public function cancelInvitation(int $invitationId): void
    {
        Gate::forUser(auth()->user())->authorize('addTeamMember', $this->team);

        $this->team->teamInvitations()->whereKey($invitationId)->delete();
        $this->team->refresh();
    }

    /**
     * Remove a member from the team.
     */
    public function removeMember(int $userId): void
    {
        $member = Teams::userModel()::findOrFail($userId);

        app(RemoveTeamMember::class)->remove(auth()->user(), $this->team, $member);

        $this->team->refresh();
    }

    /**
     * Leave the team yourself.
     */
    public function leaveTeam(): void
    {
        $user = auth()->user();

        app(RemoveTeamMember::class)->remove($user, $this->team, $user);

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
        $user = auth()->user();

        return [
            'members' => $this->team->allUsers(),
            'invitations' => Teams::sendsInvitations() ? $this->team->teamInvitations()->get() : collect(),
            'roles' => Teams::roles(),
            'sendsInvitations' => Teams::sendsInvitations(),
            'authUser' => $user,
            'canManage' => $user->can('addTeamMember', $this->team),
            'canUpdateMembers' => $user->can('updateTeamMember', $this->team),
            'canRemoveMembers' => $user->can('removeTeamMember', $this->team),
        ];
    }
}; ?>

<div class="space-y-6">
    {{-- Add / invite member --}}
    @if ($canManage)
        <x-teams::card
            :title="$sendsInvitations ? __('Invite Team Member') : __('Add Team Member')"
            :description="$sendsInvitations
                ? __('Invite a new member by email. They will receive a link to join the team.')
                : __('Add an existing user to the team by their email address.')"
        >
            <form wire:submit="addTeamMember" class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <x-teams::label for="member-email">{{ __('Email') }}</x-teams::label>
                        <x-teams::input id="member-email" type="email" wire:model="email" class="mt-1.5" placeholder="name@example.com" />
                    </div>
                    <div>
                        <x-teams::label for="member-role">{{ __('Role') }}</x-teams::label>
                        <select
                            id="member-role"
                            wire:model="role"
                            class="mt-1.5 block w-full rounded-lg border-0 bg-white px-3.5 py-2 text-sm text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 focus:ring-2 focus:ring-inset focus:ring-zinc-900 dark:bg-zinc-800 dark:text-white dark:ring-zinc-700 dark:focus:ring-white"
                        >
                            @foreach ($roles as $key => $roleOption)
                                <option value="{{ $key }}" wire:key="add-role-{{ $key }}">{{ $roleOption->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <x-teams::error :messages="$errors->getBag('addTeamMember')->get('email')" />

                @if ($selected = ($roles[$role] ?? null))
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $selected->description }}</p>
                @endif

                <div class="flex items-center justify-end gap-3">
                    <span
                        x-data="{ shown: false }"
                        @member-added.window="shown = true; setTimeout(() => shown = false, 2500)"
                        x-show="shown"
                        x-transition
                        x-cloak
                        class="text-sm text-zinc-500 dark:text-zinc-400"
                    >{{ $sendsInvitations ? __('Invitation sent.') : __('Member added.') }}</span>

                    <x-teams::button type="submit" wire:loading.attr="disabled" wire:target="addTeamMember">
                        {{ $sendsInvitations ? __('Send Invitation') : __('Add Member') }}
                    </x-teams::button>
                </div>
            </form>
        </x-teams::card>
    @endif

    {{-- Pending invitations --}}
    @if ($invitations->isNotEmpty())
        <x-teams::card :title="__('Pending Invitations')" :description="__('These people have been invited but have not yet joined.')">
            <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($invitations as $invitation)
                    <li wire:key="invitation-{{ $invitation->id }}" class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $invitation->email }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ ($roles[$invitation->role] ?? null)?->name ?? __('Member') }}</p>
                        </div>
                        @if ($canManage)
                            <x-teams::button
                                variant="ghost"
                                wire:click="cancelInvitation({{ $invitation->id }})"
                                wire:confirm="{{ __('Cancel this invitation?') }}"
                                class="text-red-600! hover:bg-red-50! dark:hover:bg-red-950/40!"
                            >
                                {{ __('Cancel') }}
                            </x-teams::button>
                        @endif
                    </li>
                @endforeach
            </ul>
        </x-teams::card>
    @endif

    {{-- Members --}}
    <x-teams::card :title="__('Team Members')" :description="__('Everyone who has access to this team.')">
        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach ($members as $member)
                @php($isOwner = (int) $member->getKey() === (int) $team->user_id)
                @php($isSelf = $authUser->is($member))
                <li wire:key="member-{{ $member->getKey() }}" class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                    <div class="flex items-center gap-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-zinc-100 text-sm font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            {{ strtoupper(mb_substr($member->name ?? '?', 0, 1)) }}
                        </span>
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                {{ $member->name }}
                                @if ($isSelf)
                                    <span class="text-xs text-zinc-400">({{ __('You') }})</span>
                                @endif
                            </p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $member->email }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        @if ($isOwner)
                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ __('Owner') }}</span>
                        @else
                            @if ($canUpdateMembers)
                                <select
                                    wire:change="updateRole({{ $member->getKey() }}, $event.target.value)"
                                    class="rounded-lg border-0 bg-white py-1.5 pl-3 pr-8 text-xs text-zinc-700 ring-1 ring-inset ring-zinc-300 focus:ring-2 focus:ring-inset focus:ring-zinc-900 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700 dark:focus:ring-white"
                                >
                                    @foreach ($roles as $key => $roleOption)
                                        <option value="{{ $key }}" wire:key="member-{{ $member->getKey() }}-role-{{ $key }}" @selected($member->membership?->role === $key)>{{ $roleOption->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                    {{ ($roles[$member->membership?->role] ?? null)?->name ?? __('Member') }}
                                </span>
                            @endif

                            @if ($isSelf)
                                <x-teams::button
                                    variant="ghost"
                                    wire:click="leaveTeam"
                                    wire:confirm="{{ __('Are you sure you want to leave this team?') }}"
                                    class="text-red-600! hover:bg-red-50! dark:hover:bg-red-950/40!"
                                >
                                    {{ __('Leave') }}
                                </x-teams::button>
                            @elseif ($canRemoveMembers)
                                <x-teams::button
                                    variant="ghost"
                                    wire:click="removeMember({{ $member->getKey() }})"
                                    wire:confirm="{{ __('Remove this member from the team?') }}"
                                    class="text-red-600! hover:bg-red-50! dark:hover:bg-red-950/40!"
                                >
                                    {{ __('Remove') }}
                                </x-teams::button>
                            @endif
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </x-teams::card>
</div>
