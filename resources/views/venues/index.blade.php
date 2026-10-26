@extends('layouts.app')

@section('title', 'Les stades du XV de France — Tous les terrains, pays par pays')

@section('meta_description', 'Les ' . $venues->count() . ' stades où le XV de France a joué depuis 1906, dans ' . $countries->count() . ' pays, avec le bilan de la France dans chacun et tous les matches qui s\'y sont disputés.')

@section('breadcrumb')
    <x-breadcrumb :items="['Stades' => null]" />
@endsection

@section('content')

    <x-page.hero kicker="Les terrains" title="Les stades"
                 :subtitle="$venues->count() . ' stades dans ' . $countries->count() . ' pays ont accueilli le XV de France.'">
        <nav aria-label="Pays" class="mt-8 flex flex-wrap gap-2">
            @foreach($countries as $group)
                <a href="#{{ Str::slug($group->country?->name ?? 'autres') }}"
                   class="rounded-full bg-white/10 px-3 py-1 text-sm text-white hover:bg-white/20 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-white/70">
                    <span aria-hidden="true">{{ $group->country?->flag_emoji }}</span> {{ $group->country?->name ?? 'Autres' }}
                    <span class="tabular-nums text-blue-200">{{ $group->venues->count() }}</span>
                </a>
            @endforeach
        </nav>
    </x-page.hero>

    <div class="space-y-12 py-12 lg:space-y-16 lg:py-16">
        @foreach($countries as $group)
            @php $r = $group->record; @endphp
            <section id="{{ Str::slug($group->country?->name ?? 'autres') }}" class="scroll-mt-6" aria-labelledby="titre-{{ $loop->index }}">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1">
                        <h2 id="titre-{{ $loop->index }}" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">
                            <span aria-hidden="true">{{ $group->country?->flag_emoji }}</span> {{ $group->country?->name ?? 'Autres' }}
                        </h2>
                        <p class="text-sm tabular-nums text-texte-2">
                            {{ $group->venues->count() }} stade{{ $group->venues->count() > 1 ? 's' : '' }}
                            · {{ $r->total }} match{{ $r->total > 1 ? 'es' : '' }}
                            · <span class="text-gagne">{{ $r->wins }} V</span> · <span class="text-egal">{{ $r->draws }} N</span> · <span class="text-perdu">{{ $r->losses }} D</span>
                            · <span class="font-semibold text-encre">{{ $r->winPctLabel() }}</span>
                        </p>
                    </div>

                    <div class="mt-4 overflow-x-auto rounded-xl border border-filet bg-white">
                        <table class="w-full min-w-[36rem] text-sm">
                            <thead>
                                <tr class="border-b border-filet text-left text-xs uppercase tracking-wider text-texte-2">
                                    <th scope="col" class="px-5 py-3 font-medium">Stade</th>
                                    <th scope="col" class="px-3 py-3 font-medium">Ville</th>
                                    <th scope="col" class="px-3 py-3 text-right font-medium">Matches</th>
                                    <th scope="col" class="px-3 py-3 text-right font-medium">V</th>
                                    <th scope="col" class="px-3 py-3 text-right font-medium">N</th>
                                    <th scope="col" class="px-3 py-3 text-right font-medium">D</th>
                                    <th scope="col" class="px-5 py-3 text-right font-medium">Victoires</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-filet">
                                @foreach($group->venues as $venue)
                                    <tr class="hover:bg-papier">
                                        <th scope="row" class="px-5 py-2 text-left font-medium text-encre">
                                            <div class="flex items-center gap-3">
                                                @if($venue->photo_path)
                                                    <img src="{{ $venue->photoThumbPath() }}" alt="" width="56" height="40" loading="lazy" decoding="async"
                                                         class="h-10 w-14 shrink-0 rounded-md bg-papier-2 object-cover">
                                                @else
                                                    <span class="h-10 w-14 shrink-0 rounded-md bg-papier-2" aria-hidden="true"></span>
                                                @endif
                                                <span>
                                                    <a href="{{ route('venues.show', $venue) }}" class="hover:underline focus-visible:outline-hidden focus-visible:underline">{{ $venue->name }}</a>
                                                    @if($venue->formerNames()->isNotEmpty())
                                                        <span class="block text-xs font-normal text-texte-2">anciennement {{ $venue->formerNames()->pluck('name')->reverse()->implode(', ') }}</span>
                                                    @endif
                                                </span>
                                            </div>
                                        </th>
                                        <td class="px-3 py-2.5 text-texte-2">{{ $venue->city }}</td>
                                        <td class="px-3 py-2.5 text-right tabular-nums">{{ $venue->record->total }}</td>
                                        <td class="px-3 py-2.5 text-right tabular-nums">{{ $venue->record->wins }}</td>
                                        <td class="px-3 py-2.5 text-right tabular-nums">{{ $venue->record->draws }}</td>
                                        <td class="px-3 py-2.5 text-right tabular-nums">{{ $venue->record->losses }}</td>
                                        <td class="px-5 py-2.5 text-right font-semibold tabular-nums">{{ $venue->record->winPctLabel() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endforeach
    </div>

@endsection
