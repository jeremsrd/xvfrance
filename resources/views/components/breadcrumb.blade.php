@props(['items' => []])

{{-- Fil d'Ariane intégré au bandeau sombre : ['Libellé' => url, 'Page courante' => null] --}}
<nav aria-label="Fil d'Ariane" class="text-xs sm:text-sm">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-blue-200/80">
        <li><a href="{{ route('home') }}" class="hover:text-white focus-visible:outline-hidden focus-visible:underline">Accueil</a></li>
        @foreach($items as $label => $url)
            <li aria-hidden="true" class="text-blue-300/40">/</li>
            <li class="min-w-0">
                @if($url)
                    <a href="{{ $url }}" class="hover:text-white focus-visible:outline-hidden focus-visible:underline">{{ $label }}</a>
                @else
                    <span aria-current="page" class="block truncate text-white/90">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
