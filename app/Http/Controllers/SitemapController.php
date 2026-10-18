<?php

namespace App\Http\Controllers;

use App\Enums\CoachRole;
use App\Models\Club;
use App\Models\Coach;
use App\Models\Competition;
use App\Models\CompetitionEdition;
use App\Models\Country;
use App\Models\Player;
use App\Models\RugbyMatch;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /** Toutes les pages publiques indexables, sans les variantes filtrées des listes. */
    public function index(): Response
    {
        $urls = collect([
            'home', 'matches.index', 'players.index', 'clubs.index', 'opponents.index',
            'competitions.index', 'coaches.index', 'records.index', 'records.players', 'venues.index',
        ])->map(fn (string $name) => route($name));

        $urls = $urls
            ->merge(RugbyMatch::orderBy('match_date')->pluck('slug')->map(fn ($slug) => route('matches.show', $slug)))
            ->merge(Player::has('lineups')->orderBy('slug')->pluck('slug')->map(fn ($slug) => route('players.show', $slug)))
            ->merge(Club::orderBy('slug')->pluck('slug')->map(fn ($slug) => route('clubs.show', $slug)))
            ->merge(Country::whereIn('id', RugbyMatch::select('opponent_id'))->orderBy('code')->pluck('code')
                ->map(fn ($code) => route('opponents.show', $code)))
            ->merge(Competition::has('editions')->orderBy('id')->pluck('id')->map(fn ($id) => route('competitions.show', $id)))
            ->merge(CompetitionEdition::has('matches')->orderBy('id')->pluck('id')->map(fn ($id) => route('editions.show', $id)))
            ->merge(Coach::whereHas('tenures', fn ($q) => $q->where('role', CoachRole::SELECTIONNEUR))->orderBy('id')->pluck('id')
                ->map(fn ($id) => route('coaches.show', $id)));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
            . $urls->map(fn (string $url) => '<url><loc>' . e($url) . '</loc></url>')->implode("\n")
            . "\n</urlset>\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
