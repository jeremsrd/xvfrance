@props(['number', 'france' => true, 'size' => 'md', 'photo' => null])

@php
    $sizes = ['sm' => 'h-7 w-7 text-sm', 'md' => 'h-9 w-9 text-base', 'lg' => 'h-11 w-11 text-lg'];
    $tone = $france
        ? 'bg-bleu-france text-white ring-white/25'
        : 'bg-white text-encre ring-encre/15';
@endphp

{{-- Portrait du joueur à la place du numéro quand il est connu ; le numéro reste lu par les lecteurs d'écran --}}
@if($photo)
    <span {{ $attributes->merge(['class' => "relative inline-flex shrink-0 overflow-hidden rounded-full ring-1 {$sizes[$size]} {$tone}"]) }} title="N° {{ $number }}">
        <img src="{{ $photo }}" alt="" loading="lazy" class="h-full w-full object-cover object-top">
        <span class="sr-only">{{ $number }}</span>
    </span>
@else
    <span {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center rounded-full font-display font-bold tabular-nums ring-1 {$sizes[$size]} {$tone}"]) }}>{{ $number }}</span>
@endif
