<?php

namespace App\Livewire;

use App\Models\Competition;
use App\Models\RugbyMatch;
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

    // Pre-applied filter (for opponent page reuse)
    public ?int $opponentId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'competition' => ['except' => ''],
        'result' => ['except' => ''],
        'decade' => ['except' => ''],
        'location' => ['except' => ''],
    ];

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

        $totalCount = $query->count();

        $matches = $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate(20);

        $competitions = Competition::orderBy('name')->get();

        return view('livewire.match-list', [
            'matches' => $matches,
            'competitions' => $competitions,
            'totalCount' => $totalCount,
        ])->layout('layouts.app', [
            'title' => 'Tous les matches du XV de France',
        ]);
    }
}
