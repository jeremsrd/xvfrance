@props(['number', 'france' => true, 'size' => 'md'])

@php
    $sizes = ['sm' => 'h-7 w-7 text-sm', 'md' => 'h-9 w-9 text-base', 'lg' => 'h-11 w-11 text-lg'];
    $tone = $france
        ? 'bg-bleu-france text-white ring-white/25'
        : 'bg-white text-encre ring-encre/15';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center rounded-full font-display font-bold tabular-nums ring-1 {$sizes[$size]} {$tone}"]) }}>{{ $number }}</span>
