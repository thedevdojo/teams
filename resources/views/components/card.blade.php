@props(['title' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900']) }}>
    @if ($title || $description)
        <div class="border-b border-zinc-100 px-6 py-4 dark:border-zinc-800/80">
            @if ($title)
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">{{ $title }}</h3>
            @endif
            @if ($description)
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
            @endif
        </div>
    @endif

    <div class="px-6 py-5">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="flex items-center justify-end gap-3 rounded-b-xl border-t border-zinc-100 bg-zinc-50 px-6 py-3 dark:border-zinc-800/80 dark:bg-zinc-900/40">
            {{ $footer }}
        </div>
    @endisset
</div>
