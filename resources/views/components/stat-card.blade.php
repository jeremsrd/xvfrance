@props(['value', 'label', 'subtitle' => null, 'color' => 'bleu-france'])

@php
    $borderColor = match($color) {
        'green' => 'border-t-green-600',
        'red' => 'border-t-red-600',
        'yellow' => 'border-t-yellow-500',
        default => 'border-t-bleu-france',
    };
@endphp

<div {{ $attributes->merge(['class' => "bg-white rounded-lg shadow-sm border border-gray-200 $borderColor border-t-4 p-6"]) }}>
    <div class="text-3xl font-bold tracking-tight text-gray-900">{{ $value }}</div>
    <div class="mt-1 text-sm font-medium text-gray-500">{{ $label }}</div>
    @if($subtitle)
        <div class="mt-2 text-xs text-gray-400">{{ $subtitle }}</div>
    @endif
</div>
