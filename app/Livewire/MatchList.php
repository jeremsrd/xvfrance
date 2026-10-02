<?php

namespace App\Livewire;

use App\Models\Competition;
use App\Models\RugbyMatch;
use App\Support\RecordSummary;
use Livewire\Component;
use Livewire\WithPagination;

class MatchList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $competition = '';
    public string $result = '';
    public string $decade = '';
    public string $location = '';
    public string $sortField = 'match_date';
    public string $sortDirection = 'desc';

    /** Tri proposé à l'utilisateur : recent, ancien, points */
    public string $order = 'recent';

    // Pre-applied filter (for opponent page reuse)
    public ?int $opponentId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'competition' => ['except' => ''],
        'result' => ['except' => ''],
        'decade' => ['except' => ''],
        'location' => ['except' => ''],
        'order' => ['except' => 'recent'],
    ];

    public function updatingOrder()
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'competition', 'result', 'decade', 'location', 'order']);
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'partials.pagination';
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCompetition()
    {
        $this->resetPage();
    }

    public function updatingResult()
    {
        $this->resetPage();
    }

    public function updatingDecade()
    {
        $this->resetPage();
    }

    public function updatingLocation()
    {
        $this->resetPage();
    }

    public function sort(string $field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function render()
    {
        $query = RugbyMatch::with(['opponent', 'venue', 'edition.competition']);

        if ($this->opponentId) {
            $query->where('opponent_id', $this->opponentId);
        }

        if ($this->search) {
            $query->whereHas('opponent', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->competition) {
            $query->whereHas('edition.competition', function ($q) {
                $q->where('id', $this->competition);
            });
        }

        if ($this->result) {
            match ($this->result) {
                'victoire' => $query->whereColumn('france_score', '>', 'opponent_score'),
                'defaite' => $query->whereColumn('france_score', '<', 'opponent_score'),
                'nul' => $query->whereColumn('france_score', '=', 'opponent_score'),
                default => null,
            };
        }

        if ($this->decade) {
            $start = (int) $this->decade;
            $query->whereYear('match_date', '>=', $start)
                  ->whereYear('match_date', '<', $start + 10);
        }

        if ($this->location) {
            match ($this->location) {
                'domicile' => $query->where('is_home', true),
                'exterieur' => $query->where('is_home', false),
                default => null,
            };
        }

        $summary = RecordSummary::fromQuery($query);
        $totalCount = $summary->total;

        [$field, $direction] = match ($this->order) {
            'ancien' => ['match_date', 'asc'],
            'points' => ['france_score', 'desc'],
            default => ['match_date', 'desc'],
        };

        $matches = $query->orderBy($field, $direction)->orderBy('id', $direction)
            ->paginate(25);

        $competitions = Competition::orderBy('name')->get();

        return view('livewire.match-list', [
            'matches' => $matches,
            'competitions' => $competitions,
            'totalCount' => $totalCount,
            'summary' => $summary,
            'allCount' => RugbyMatch::count(),
            'filtered' => $this->search !== '' || $this->competition !== '' || $this->result !== '' || $this->decade !== '' || $this->location !== '',
        ])->layout('layouts.app', [
            'title' => 'Tous les matches du XV de France',
        ]);
    }
}
