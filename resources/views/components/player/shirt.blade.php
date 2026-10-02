@props(['number' => null, 'france' => true])

{{-- Maillot stylisé avec le numéro le plus porté --}}
<svg {{ $attributes->merge(['class' => 'h-40 w-40']) }} viewBox="0 0 120 120" role="img" aria-label="Maillot{{ $number ? ' n° ' . $number : '' }}">
    <path d="M38 14 L24 20 L6 38 L18 54 L28 46 L28 108 L92 108 L92 46 L102 54 L114 38 L96 20 L82 14 C78 22 70 26 60 26 C50 26 42 22 38 14 Z"
          fill="{{ $france ? '#002395' : '#F7F5F0' }}" stroke="{{ $france ? 'rgba(255,255,255,.35)' : 'rgba(11,26,59,.25)' }}" stroke-width="1.5" stroke-linejoin="round"/>
    <path d="M38 14 C42 22 50 26 60 26 C70 26 78 22 82 14" fill="none" stroke="{{ $france ? '#FFFFFF' : '#0B1A3B' }}" stroke-width="3" stroke-linecap="round"/>
    @if($france)
        <g opacity=".9"><rect x="28" y="96" width="21.3" height="4" fill="#002395"/><rect x="49.3" y="96" width="21.4" height="4" fill="#FFFFFF"/><rect x="70.7" y="96" width="21.3" height="4" fill="#ED2939"/></g>
    @endif
    @if($number)
        <text x="60" y="80" text-anchor="middle" font-family="Barlow Condensed, sans-serif" font-weight="700" font-size="40"
              fill="{{ $france ? '#FFFFFF' : '#0B1A3B' }}">{{ $number }}</text>
    @endif
</svg>
