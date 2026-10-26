@extends('layouts.app')

@php
    use App\Support\FrenchDate;
    use App\Support\FrenchText;
    use App\Support\RecordSummary;

    $first = $matches->last();
    $last = $matches->first();
    $formerNames = $venue->formerNames();
    $decades = $matches->sortBy('match_date')->groupBy(fn ($m) => intdiv($m->match_date->year, 10) * 10);
    $opponents = $matches->groupBy('opponent_id')
        ->map(fn ($group) => ['country' => $group->first()->opponent, 'record' => RecordSummary::fromMatches($group)])
        ->sortByDesc(fn ($row) => $row['record']->total);
    $share = fn ($n) => $record->total ? round($n / $record->total * 100, 2) : 0;
@endphp

@section('title', $venue->name . ($venue->city ? ', ' . $venue->city : '') . ' — Les matches du XV de France')

@section('meta_description', $venue->name . ($venue->city ? ' (' . $venue->city . ')' : '') . ' : ' . ($record->total > 1 ? 'les ' : 'le ') . FrenchText::count($record->total, 'match', 'matches') . ' du XV de France ' . FrenchText::years($first->match_date->year, $last->match_date->year) . ', ' . FrenchText::count($record->wins, 'victoire') . ', ' . FrenchText::count($record->draws, 'nul') . ', ' . FrenchText::count($record->losses, 'défaite') . '.')

@section('breadcrumb')
    <x-breadcrumb :items="['Stades' => route('venues.index'), $venue->name => null]" />
@endsection

@section('content')
    {{-- ═══ Bandeau ═══ --}}
    <x-page.hero kicker="Le stade" :title="$venue->name">
        <p class="mt-4 max-w-2xl font-serif text-lg leading-relaxed text-blue-100">
            @if($venue->country)<span aria-hidden="true">{{ $venue->country->flag_emoji }}</span>@endif
            {{ collect([$venue->city, $venue->country?->name])->filter()->unique()->implode(', ') }}@if($formerNames->isNotEmpty()) · anciennement {{ $formerNames->pluck('name')->reverse()->implode(', ') }}@endif.
            {{ ucfirst(FrenchText::count($record->total, 'match', 'matches')) }} du XV de France,
            @if($first->is($last))
                le {{ FrenchDate::long($first->match_date) }}.
            @else
                du {{ FrenchDate::long($first->match_date) }} au {{ FrenchDate::long($last->match_date) }}.
            @endif
        </p>

        <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:items-end">
            <dl class="grid grid-cols-3 gap-4">
                @foreach([['Victoires', $record->wins, 'text-emerald-300'], ['Nuls', $record->draws, 'text-amber-200'], ['Défaites', $record->losses, 'text-red-300']] as [$label, $value, $color])
                    <div>
                        <dt class="text-xs uppercase tracking-[0.16em] text-blue-200">{{ $label }}</dt>
                        <dd class="font-display text-5xl font-bold tabular-nums {{ $color }}">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
            <div>
                <div class="flex items-baseline justify-between text-sm">
                    <span class="text-blue-200">Bilan du XV de France</span>
                    <span class="font-display text-2xl font-bold">{{ $record->winPctLabel() }} <span class="text-sm font-normal text-blue-200">de victoires</span></span>
                </div>
                <div class="mt-2 flex h-3 overflow-hidden rounded-full bg-white/10" aria-hidden="true">
                    <span class="bg-emerald-400" style="width: {{ $share($record->wins) }}%"></span>
                    <span class="bg-amber-300" style="width: {{ $share($record->draws) }}%"></span>
                    <span class="bg-rouge-france" style="width: {{ $share($record->losses) }}%"></span>
                </div>
            </div>
        </div>
    </x-page.hero>

    {{-- ═══ Photo du stade (Wikimedia Commons) : le crédit et la licence sont exigés par la licence ═══ --}}
    @if($venue->photo_path)
        <figure class="mx-auto max-w-6xl px-4 pt-12 sm:px-6 lg:px-8 lg:pt-16">
            <img src="{{ $venue->photo_path }}" width="{{ $venue->photo_width }}" height="{{ $venue->photo_height }}"
                 alt="{{ $venue->name }}, {{ $venue->city }}" decoding="async"
                 class="h-64 w-full rounded-2xl bg-papier-2 object-cover shadow-sm ring-1 ring-filet sm:h-96">
            <figcaption class="mt-2 text-xs text-texte-2">
                Photo : {{ $venue->photo_author ?? 'auteur inconnu' }}
                @if($venue->photo_license)
                    ·
                    @if($venue->photo_license_url)
                        <a href="{{ $venue->photo_license_url }}" rel="license noopener" target="_blank" class="underline hover:text-encre">{{ $venue->photo_license }}</a>
                    @else
                        {{ $venue->photo_license }}
                    @endif
                @endif
                · <a href="{{ $venue->photo_source_url }}" rel="noopener" target="_blank" class="underline hover:text-encre">Wikimedia Commons</a>
            </figcaption>
        </figure>
    @endif

    {{-- ═══ Adversaires reçus ═══ --}}
    @if($opponents->count() > 1)
        <section class="py-12 lg:py-16" aria-labelledby="adversaires">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <h2 id="adversaires" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Par adversaire</h2>
                <ul class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($opponents as ['country' => $country, 'record' => $r])
                        <li>
                            <a href="{{ route('opponents.show', $country->code) }}" class="group flex items-center justify-between gap-3 rounded-xl border border-filet bg-white px-4 py-3 hover:border-encre/30">
                                <span class="font-semibold text-encre group-hover:underline"><span aria-hidden="true">{{ $country->flag_emoji }}</span> {{ $country->name }}</span>
                                <span class="text-sm tabular-nums text-texte-2">{{ $r->total }} · <span class="text-gagne">{{ $r->wins }} V</span> · <span class="text-egal">{{ $r->draws }} N</span> · <span class="text-perdu">{{ $r->losses }} D</span></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ═══ Matches par décennie ═══ --}}
    <section class="border-t border-filet bg-white py-12 lg:py-16" aria-labelledby="matches">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 id="matches" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">{{ $record->total > 1 ? 'Tous les matches' : 'Le match' }}</h2>
            <div class="mt-8 space-y-10">
                @foreach($decades->reverse() as $decade => $group)
                    @php $dr = RecordSummary::fromMatches($group); @endphp
                    <div>
                        <div class="flex flex-wrap items-baseline justify-between gap-3 border-b border-filet pb-2">
                            <h3 class="font-display text-2xl font-bold tabular-nums text-encre">Années {{ $decade }}</h3>
                            <span class="text-sm tabular-nums text-texte-2">{{ FrenchText::count($dr->total, 'match', 'matches') }} · <span class="text-gagne">{{ $dr->wins }} V</span> · <span class="text-egal">{{ $dr->draws }} N</span> · <span class="text-perdu">{{ $dr->losses }} D</span></span>
                        </div>
                        <ol class="mt-2 divide-y divide-filet">
                            @foreach($group->sortByDesc('match_date') as $m)
                                <li><x-match.row :match="$m" class="rounded-lg !px-2" /></li>
                            @endforeach
                        </ol>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
