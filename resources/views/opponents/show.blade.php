@extends('layouts.app')

@php
    use App\Support\FrenchDate;
    use App\Support\RecordSummary;

    $c = $country;
    $first = $matches->last();
    $last = $matches->first();
    $home = RecordSummary::fromMatches($matches->where('is_home', true)->where('is_neutral', false));
    $away = RecordSummary::fromMatches($matches->where('is_home', false)->where('is_neutral', false));
    $neutral = RecordSummary::fromMatches($matches->where('is_neutral', true));
    $decades = $matches->sortBy('match_date')->groupBy(fn ($m) => intdiv($m->match_date->year, 10) * 10);
    $recent = $matches->take(10)->reverse();
    $share = fn ($r, $n) => $r->total ? round($n / $r->total * 100, 2) : 0;
    $tone = fn ($result) => match ($result) { 'Victoire' => 'bg-gagne', 'Défaite' => 'bg-perdu', default => 'bg-egal' };
    $letter = fn ($result) => match ($result) { 'Victoire' => 'V', 'Défaite' => 'D', default => 'N' };
@endphp

@section('title', 'France – ' . $c->name . ' : le bilan complet depuis ' . ($first?->match_date->year ?? ''))

@section('meta_description', 'Le bilan du XV de France face ' . $c->withArticle('à') . ' : ' . $record->total . ' matches, ' . $record->wins . ' victoires, ' . $record->draws . ' nuls, ' . $record->losses . ' défaites.')

@section('breadcrumb')
    <x-breadcrumb :items="['Adversaires' => route('opponents.index'), $c->name => null]" />
@endsection

@section('content')
    {{-- ═══ Bandeau ═══ --}}
    <section class="relative overflow-hidden bg-encre text-white">
        <div class="mx-auto max-w-6xl px-4 pb-12 pt-6 sm:px-6 lg:px-8 lg:pb-16 lg:pt-8">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">La rivalité</p>
            <h1 class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 font-display text-5xl font-bold uppercase leading-none tracking-tight sm:text-6xl">
                <span><span aria-hidden="true">🇫🇷</span> France</span>
                <span class="text-blue-300/50" aria-hidden="true">–</span>
                <span>{{ $c->name }} <span aria-hidden="true">{{ $c->flag_emoji }}</span></span>
            </h1>
            @if($first)
                <p class="mt-4 max-w-2xl font-serif text-lg text-blue-100">
                    {{ $record->total }} confrontation{{ $record->total > 1 ? 's' : '' }} depuis le
                    {{ FrenchDate::long($first->match_date) }}@if($last && $last->isNot($first)), la dernière le {{ FrenchDate::long($last->match_date) }}@endif.
                </p>
            @endif

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
                        <span class="bg-emerald-400" style="width: {{ $share($record, $record->wins) }}%"></span>
                        <span class="bg-amber-300" style="width: {{ $share($record, $record->draws) }}%"></span>
                        <span class="bg-rouge-france" style="width: {{ $share($record, $record->losses) }}%"></span>
                    </div>
                    @if($recent->isNotEmpty())
                        <div class="mt-4 flex flex-wrap items-center gap-1.5">
                            <span class="mr-1 text-xs text-blue-200">{{ $recent->count() }} dernières</span>
                            @foreach($recent as $m)
                                <a href="{{ route('matches.show', $m) }}" title="{{ $m->match_date->format('d/m/Y') }} : {{ $m->france_score }}–{{ $m->opponent_score }}"
                                   class="flex h-6 w-6 items-center justify-center rounded text-[11px] font-bold text-white {{ $tone($m->result) }} hover:ring-2 hover:ring-white/60">{{ $letter($m->result) }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ═══ Repères ═══ --}}
    <section class="py-12 lg:py-16">
        <div class="mx-auto grid max-w-6xl gap-4 px-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
            @foreach(array_filter([['À domicile', $home], ['À l\'extérieur', $away], ['Terrain neutre', $neutral]], fn ($x) => $x[1]->total > 0) as [$label, $r])
                <div class="rounded-2xl border border-filet bg-white p-5">
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-texte-2">{{ $label }}</div>
                    <div class="mt-1 font-display text-3xl font-bold tabular-nums text-encre">{{ $r->winPctLabel(0) }}</div>
                    <div class="text-sm tabular-nums text-texte-2">{{ $r->total }} matches · {{ $r->wins }} V · {{ $r->draws }} N · {{ $r->losses }} D</div>
                </div>
            @endforeach
            @foreach(array_filter([['Plus large victoire', $biggestWin, 'text-gagne'], ['Plus lourde défaite', $biggestLoss, 'text-perdu']], fn ($x) => $x[1]) as [$label, $m, $color])
                <a href="{{ route('matches.show', $m) }}" class="group rounded-2xl border border-filet bg-white p-5 hover:border-encre/30">
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-texte-2">{{ $label }}</div>
                    <div class="mt-1 font-display text-3xl font-bold tabular-nums {{ $color }}">{{ $m->france_score }}–{{ $m->opponent_score }}</div>
                    <div class="text-sm text-texte-2 group-hover:underline">{{ FrenchDate::long($m->match_date) }}</div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ═══ Matches par décennie ═══ --}}
    <section class="border-t border-filet bg-white py-12 lg:py-16" aria-labelledby="matches">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <h2 id="matches" class="font-display text-3xl font-bold uppercase tracking-tight text-encre">Toutes les confrontations</h2>
            <div class="mt-8 space-y-10">
                @foreach($decades->reverse() as $decade => $group)
                    @php $dr = RecordSummary::fromMatches($group); @endphp
                    <div>
                        <div class="flex flex-wrap items-baseline justify-between gap-3 border-b border-filet pb-2">
                            <h3 class="font-display text-2xl font-bold tabular-nums text-encre">Années {{ $decade }}</h3>
                            <span class="text-sm tabular-nums text-texte-2">{{ $dr->total }} match{{ $dr->total > 1 ? 'es' : '' }} · <span class="text-gagne">{{ $dr->wins }} V</span> · <span class="text-egal">{{ $dr->draws }} N</span> · <span class="text-perdu">{{ $dr->losses }} D</span></span>
                        </div>
                        <ol class="mt-2 divide-y divide-filet">
                            @foreach($group->sortByDesc('match_date') as $m)
                                <li><x-match.row :match="$m" :venue="true" class="rounded-lg !px-2" /></li>
                            @endforeach
                        </ol>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
