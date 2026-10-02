@props(['type', 'class' => 'h-5 w-5'])

{{-- Icônes des actions de jeu : ballon (essai), poteaux (buts), carton, flèches (remplacement) --}}
@switch($type)
    @case('essai')
    @case('essai_penalite')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <ellipse cx="12" cy="12" rx="9.5" ry="5.8" transform="rotate(-35 12 12)" fill="currentColor"/>
            <path d="M8.2 15.6 15.8 8.4" stroke="white" stroke-width="1.2" stroke-linecap="round"/>
            <path d="M10.6 11.4l1.6 1.4M12 10.1l1.6 1.4M9.2 12.7l1.6 1.4" stroke="white" stroke-width="1" stroke-linecap="round"/>
        </svg>
        @break
    @case('transformation')
    @case('penalite')
    @case('drop')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M7 3v18M17 3v18M7 14h10" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
        </svg>
        @break
    @case('carton_jaune')
    @case('carton_rouge')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" aria-hidden="true">
            <rect x="6.5" y="3.5" width="11" height="17" rx="1.8" transform="rotate(8 12 12)"
                  fill="{{ $type === 'carton_jaune' ? '#F2C230' : '#D7262E' }}" stroke="rgba(0,0,0,.18)"/>
        </svg>
        @break
    @case('remplacement')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M7 4v13m0 0-3-3m3 3 3-3" stroke="#1D7348" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M17 20V7m0 0-3 3m3-3 3 3" stroke="#B3261E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        @break
@endswitch
