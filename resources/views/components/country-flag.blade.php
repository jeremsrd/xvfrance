@props(['country', 'showName' => true, 'class' => ''])

<span class="inline-flex items-center gap-1.5 {{ $class }}">
    <span class="text-lg leading-none">{{ $country->flag_emoji }}</span>
    @if($showName)
        <span>{{ $country->name }}</span>
    @endif
</span>
