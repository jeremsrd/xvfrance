@extends('layouts.app')

@section('title', 'Les clubs des internationaux du XV de France')

@section('meta_description', 'Les ' . $clubs->count() . ' clubs qui ont fourni des internationaux au XV de France depuis 1906, du Stade toulousain aux clubs disparus.')

@section('breadcrumb')
    <x-breadcrumb :items="['Clubs' => null]" />
@endsection

@section('content')

    <x-page.hero kicker="Les viviers" title="Les clubs"
                 :subtitle="$clubs->count() . ' clubs ont fourni au moins un international au XV de France.'" />

    <section class="py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="overflow-x-auto rounded-xl border border-filet bg-white">
                <table class="w-full min-w-[32rem] text-sm">
                    <thead>
                        <tr class="border-b border-filet text-left text-xs uppercase tracking-wider text-texte-2">
                            <th scope="col" class="px-5 py-3 font-medium">Club</th>
                            <th scope="col" class="px-3 py-3 font-medium">Ville</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium">Internationaux</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-filet">
                        @foreach($clubs as $club)
                            <tr>
                                <th scope="row" class="px-5 py-2.5 text-left font-medium text-encre">
                                    <a href="{{ route('clubs.show', $club) }}" class="hover:underline focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-bleu-france">{{ $club->name }}</a>
                                </th>
                                <td class="px-3 py-2.5 text-texte-2">
                                    @if($club->country)<span aria-hidden="true">{{ $club->country->flag_emoji }}</span>@endif
                                    {{ $club->city }}
                                </td>
                                <td class="px-5 py-2.5 text-right font-semibold tabular-nums">{{ $club->internationals_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-texte-2">
                Clubs de chaque international pendant sa carrière en équipe de France, d'après la liste des internationaux
                (jusqu'à 2016) et equipe-france.fr pour les joueurs récents. Un club renommé reste le même club.
            </p>
        </div>
    </section>
@endsection
