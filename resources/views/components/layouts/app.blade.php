@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name', 'Teams') }}</title>

    {{--
        Use the application's compiled CSS when a Vite build exists (recommended for
        production — see the README for the @source directive you should add). Otherwise
        fall back to the Tailwind CDN so the bundled pages render out of the box.
    --}}
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css'])
    @else
        <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    @endif

    @livewireStyles
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-full bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100">
    <div class="min-h-screen">
        <header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="{{ url('/') }}" class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-zinc-900 text-sm font-bold text-white dark:bg-white dark:text-zinc-900">
                        {{ strtoupper(substr(config('app.name', 'T'), 0, 1)) }}
                    </span>
                    {{ config('app.name', 'Teams') }}
                </a>

                @auth
                    <livewire:teams.team-switcher />
                @endauth
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-300">
                    {{ session('status') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
