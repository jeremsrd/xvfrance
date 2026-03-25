<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'XV de France — L\'histoire complète depuis 1906')</title>
    <meta name="description" content="@yield('meta_description', 'Site de référence francophone sur l\'histoire du XV de France de rugby depuis 1906. Tous les matches, compositions, marqueurs et statistiques.')">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'bleu-france': '#002395',
                        'bleu-france-light': '#003399',
                        'bleu-france-dark': '#001a6e',
                        'rouge-france': '#ED2939',
                        'rouge-france-light': '#FF3344',
                        'victoire': '#198754',
                        'defaite': '#DC3545',
                        'nul': '#FFC107',
                        'or': '#D4AF37',
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @livewireStyles
</head>
<body class="min-h-screen flex flex-col bg-white text-gray-900">

    {{-- Header --}}
    <header class="bg-bleu-france text-white" x-data="{ open: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center space-x-2">
                    <span class="text-2xl font-bold tracking-tight">XV FRANCE</span>
                </a>

                {{-- Navigation desktop --}}
                <nav class="hidden md:flex items-center space-x-1">
                    <a href="{{ route('home') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-bleu-france-light transition {{ request()->routeIs('home') ? 'bg-bleu-france-light' : '' }}">Accueil</a>
                    <a href="{{ route('matches.index') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-bleu-france-light transition {{ request()->routeIs('matches.*') ? 'bg-bleu-france-light' : '' }}">Matches</a>
                    <a href="{{ route('players.index') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-bleu-france-light transition {{ request()->routeIs('players.*') ? 'bg-bleu-france-light' : '' }}">Joueurs</a>
                    <a href="{{ route('opponents.index') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-bleu-france-light transition {{ request()->routeIs('opponents.*') ? 'bg-bleu-france-light' : '' }}">Adversaires</a>
                    <a href="{{ route('competitions.index') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-bleu-france-light transition {{ request()->routeIs('competitions.*') || request()->routeIs('editions.*') ? 'bg-bleu-france-light' : '' }}">Compétitions</a>
                    <a href="{{ route('coaches.index') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-bleu-france-light transition {{ request()->routeIs('coaches.*') ? 'bg-bleu-france-light' : '' }}">Sélectionneurs</a>
                </nav>

                {{-- Hamburger mobile --}}
                <button @click="open = !open" class="md:hidden p-2 rounded-md hover:bg-bleu-france-light transition">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Navigation mobile --}}
        <div x-show="open" x-cloak x-transition class="md:hidden border-t border-bleu-france-light">
            <div class="px-4 py-3 space-y-1">
                <a href="{{ route('home') }}" class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-bleu-france-light">Accueil</a>
                <a href="{{ route('matches.index') }}" class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-bleu-france-light">Matches</a>
                <a href="{{ route('players.index') }}" class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-bleu-france-light">Joueurs</a>
                <a href="{{ route('opponents.index') }}" class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-bleu-france-light">Adversaires</a>
                <a href="{{ route('competitions.index') }}" class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-bleu-france-light">Compétitions</a>
                <a href="{{ route('coaches.index') }}" class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-bleu-france-light">Sélectionneurs</a>
            </div>
        </div>
    </header>

    {{-- Fil d'Ariane --}}
    @hasSection('breadcrumb')
    <div class="bg-gray-100 border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
            <nav class="text-sm text-gray-500">
                <a href="{{ route('home') }}" class="hover:text-bleu-france">Accueil</a>
                @yield('breadcrumb')
            </nav>
        </div>
    </div>
    @endif

    {{-- Contenu principal --}}
    <main class="flex-1">
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    {{-- Footer --}}
    <footer class="bg-gray-900 text-gray-400 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                {{-- Navigation rapide --}}
                <div>
                    <h3 class="text-white font-semibold mb-4">Navigation</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('home') }}" class="hover:text-white transition">Accueil</a></li>
                        <li><a href="{{ route('matches.index') }}" class="hover:text-white transition">Tous les matches</a></li>
                        <li><a href="{{ route('players.index') }}" class="hover:text-white transition">Joueurs</a></li>
                        <li><a href="{{ route('opponents.index') }}" class="hover:text-white transition">Adversaires</a></li>
                        <li><a href="{{ route('competitions.index') }}" class="hover:text-white transition">Compétitions</a></li>
                        <li><a href="{{ route('coaches.index') }}" class="hover:text-white transition">Sélectionneurs</a></li>
                    </ul>
                </div>

                {{-- À propos --}}
                <div>
                    <h3 class="text-white font-semibold mb-4">À propos</h3>
                    <p class="text-sm leading-relaxed">
                        Site de référence francophone sur l'histoire du XV de France de rugby.
                        Tous les matches, compositions complètes, marqueurs et statistiques depuis 1906.
                    </p>
                </div>

                {{-- Contact --}}
                <div>
                    <h3 class="text-white font-semibold mb-4">Contact</h3>
                    <p class="text-sm leading-relaxed">
                        Une erreur, une suggestion ?<br>
                        Contactez-nous pour contribuer à ce projet.
                    </p>
                </div>
            </div>

            <div class="mt-8 pt-8 border-t border-gray-800 text-center text-sm">
                <p>xvfrance.fr — L'histoire du XV de France depuis 1906</p>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
