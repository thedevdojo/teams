@props(['type' => 'text'])

<input
    type="{{ $type }}"
    {{ $attributes->merge(['class' => 'block w-full rounded-lg border-0 bg-white px-3.5 py-2 text-sm text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 placeholder:text-zinc-400 focus:ring-2 focus:ring-inset focus:ring-zinc-900 dark:bg-zinc-800 dark:text-white dark:ring-zinc-700 dark:focus:ring-white']) }}
/>
