@props(['messages' => []])

@if (! empty($messages))
    <ul {{ $attributes->merge(['class' => 'mt-1.5 space-y-1 text-sm text-red-600 dark:text-red-400']) }}>
        @foreach ((array) $messages as $message)
            <li wire:key="error-{{ $loop->index }}">{{ $message }}</li>
        @endforeach
    </ul>
@endif
