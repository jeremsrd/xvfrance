@props(['kicker' => null, 'title', 'subtitle' => null, 'logo' => null])

{{-- Bandeau de page, dans le prolongement de l'en-tête sombre --}}
<section {{ $attributes->merge(['class' => 'relative overflow-hidden bg-encre text-white']) }}>
    <div class="mx-auto max-w-6xl px-4 pb-10 pt-6 sm:px-6 lg:px-8 lg:pb-14 lg:pt-8">
        @if($logo)
            <img src="{{ asset($logo) }}" alt="" class="mb-6 h-14 w-auto max-w-56 object-contain object-left sm:float-right sm:mb-0 sm:ml-8 sm:h-20 sm:max-w-72">
        @endif
        @if($kicker)
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">{{ $kicker }}</p>
        @endif
        <h1 class="mt-2 font-display text-5xl font-bold uppercase leading-[0.95] tracking-tight sm:text-6xl">{{ $title }}</h1>
        @if($subtitle)
            <p class="mt-4 max-w-2xl font-serif text-lg leading-relaxed text-blue-100">{{ $subtitle }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
