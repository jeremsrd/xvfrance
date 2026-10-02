<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'XV de France — L\'histoire complète depuis 1906')</title>
    <meta name="description" content="@yield('meta_description', 'Site de référence francophone sur l\'histoire du XV de France de rugby depuis 1906. Tous les matches, compositions, marqueurs et statistiques.')">
    <meta name="theme-color" content="#0B1A3B">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700&family=Barlow:wght@400;500;600;700&family=Source+Serif+4:ital,opsz,wght@0,8..60,400;0,8..60,600;1,8..60,400&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    @stack('head')
    @livewireStyles
</head>
@php
    $nav = [
        ['Matches', 'matches.index', 'matches.*'],
        ['Joueurs', 'players.index', 'players.*'],
        ['Adversaires', 'opponents.index', 'opponents.*'],
        ['Compétitions', 'competitions.index', ['competitions.*', 'editions.*']],
        ['Sélectionneurs', 'coaches.index', 'coaches.*'],
        ['Records', 'records.index', 'records.*'],
        ['Stades', 'venues.index', 'venues.*'],
    ];
    $focus = 'focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-white/70';
@endphp
<body class="flex min-h-screen flex-col bg-papier font-sans text-texte antialiased">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-encre">Aller au contenu</a>

    {{-- ═══ En-tête : ne fait qu'un avec le bandeau sombre de chaque page ═══ --}}
    <header class="bg-encre text-white" x-data="{ open: false }" @keydown.escape.window="open = false">
        <div class="flex h-1" aria-hidden="true">
            <span class="flex-1 bg-bleu-france"></span><span class="flex-1 bg-white"></span><span class="flex-1 bg-rouge-france"></span>
        </div>
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between gap-6">
                <a href="{{ route('home') }}" class="group flex items-baseline gap-2 rounded {{ $focus }}" aria-label="XV de France, accueil">
                    <span class="font-display text-3xl font-bold leading-none tracking-tight">XV</span>
                    <span class="font-serif text-lg italic leading-none text-blue-200 group-hover:text-white">de France</span>
                </a>

                <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigation principale">
                    @foreach($nav as [$label, $route, $pattern])
                        @php $active = request()->routeIs(...(array) $pattern); @endphp
                        <a href="{{ route($route) }}" @if($active) aria-current="page" @endif
                           class="relative rounded px-3 py-2 text-sm font-semibold motion-safe:transition-colors {{ $active ? 'text-white' : 'text-blue-200 hover:text-white' }} {{ $focus }}">
                            {{ $label }}
                            @if($active)<span class="absolute inset-x-3 -bottom-[13px] h-0.5 rounded-full bg-white" aria-hidden="true"></span>@endif
                        </a>
                    @endforeach
                </nav>

                <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="menu-mobile"
                        class="-mr-2 flex h-11 w-11 items-center justify-center rounded-md text-blue-100 hover:bg-white/10 lg:hidden {{ $focus }}">
                    <span class="sr-only">Menu</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path x-show="!open" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                        <path x-show="open" x-cloak stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <nav id="menu-mobile" x-show="open" x-cloak x-transition.opacity class="border-t border-white/10 lg:hidden" aria-label="Navigation principale">
            <ul class="mx-auto grid max-w-6xl grid-cols-2 gap-1 px-4 py-3 sm:px-6">
                <li><a href="{{ route('home') }}" class="block rounded-md px-3 py-3 text-sm font-semibold {{ request()->routeIs('home') ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/5' }}">Accueil</a></li>
                @foreach($nav as [$label, $route, $pattern])
                    <li><a href="{{ route($route) }}" class="block rounded-md px-3 py-3 text-sm font-semibold {{ request()->routeIs(...(array) $pattern) ? 'bg-white/10 text-white' : 'text-blue-100 hover:bg-white/5' }}">{{ $label }}</a></li>
                @endforeach
            </ul>
        </nav>

        @hasSection('breadcrumb')
            <div class="mx-auto max-w-6xl border-t border-white/10 px-4 py-3 sm:px-6 lg:px-8">
                @yield('breadcrumb')
            </div>
        @endif
    </header>

    <main id="contenu" class="flex-1">
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    {{-- ═══ Pied de page ═══ --}}
    <footer class="mt-auto bg-nuit text-blue-200/80">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid gap-10 md:grid-cols-[minmax(0,5fr)_minmax(0,4fr)_minmax(0,3fr)]">
                <div>
                    <a href="{{ route('home') }}" class="flex items-baseline gap-2 text-white">
                        <span class="font-display text-3xl font-bold leading-none">XV</span>
                        <span class="font-serif text-lg italic leading-none text-blue-200">de France</span>
                    </a>
                    <p class="mt-4 max-w-sm font-serif text-sm leading-relaxed">
                        L'histoire du XV de France de rugby depuis 1906 : tous les matches, les compositions,
                        les marqueurs, les adversaires et les sélectionneurs.
                    </p>
                </div>
                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-300">Explorer</h2>
                    <ul class="mt-4 grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                        @foreach($nav as [$label, $route])
                            <li><a href="{{ route($route) }}" class="hover:text-white">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-300">Contribuer</h2>
                    <p class="mt-4 text-sm leading-relaxed">
                        Une erreur, une feuille de match à compléter, un document d'archive ?
                        Toute contribution est la bienvenue.
                    </p>
                </div>
            </div>
            <div class="mt-10 flex flex-col gap-2 border-t border-white/10 pt-6 text-xs text-blue-300/70 sm:flex-row sm:items-center sm:justify-between">
                <p>xvfrance.fr — L'histoire du XV de France depuis 1906</p>
                <div class="flex h-1 w-16 overflow-hidden rounded-full" aria-hidden="true">
                    <span class="flex-1 bg-bleu-france"></span><span class="flex-1 bg-white"></span><span class="flex-1 bg-rouge-france"></span>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
