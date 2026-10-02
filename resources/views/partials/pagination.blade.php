{{-- Pagination des listes Livewire, dans le style du site --}}
@if ($paginator->hasPages())
    @php
        $btn = 'inline-flex min-h-[40px] min-w-[40px] items-center justify-center rounded-full px-3 text-sm font-semibold tabular-nums focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-bleu-france';
        $scrollTo = 'document.getElementById(\'liste\')?.scrollIntoView({behavior: \'smooth\'})';
    @endphp
    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-texte-2">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} sur {{ number_format($paginator->total(), 0, ',', "\u{202F}") }}
        </p>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="{{ $btn }} text-texte-2/40" aria-disabled="true">← <span class="sr-only">Précédente</span></span>
            @else
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollTo }}" class="{{ $btn }} text-encre hover:bg-papier-2" aria-label="Page précédente">←</button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="{{ $btn }} hidden text-texte-2 sm:inline-flex">…</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="{{ $btn }} bg-encre text-white" aria-current="page">{{ $page }}</span>
                        @else
                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollTo }}"
                                    class="{{ $btn }} hidden text-encre hover:bg-papier-2 sm:inline-flex" aria-label="Page {{ $page }}">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollTo }}" class="{{ $btn }} text-encre hover:bg-papier-2" aria-label="Page suivante">→</button>
            @else
                <span class="{{ $btn }} text-texte-2/40" aria-disabled="true">→ <span class="sr-only">Suivante</span></span>
            @endif
        </div>
    </nav>
@endif
