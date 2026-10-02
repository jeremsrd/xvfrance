<?php

namespace App\Livewire;

use App\Enums\PlayerPosition;
use App\Models\Country;
use App\Models\Player;
use Livewire\Component;
use Livewire\WithPagination;

class PlayerList extends Component
{
    use WithPagination;

    public string $search = '';
    /** bleus | adversaires | tous */
    public string $team = 'bleus';
    public string $country = '';
    public string $position = '';
    /** nom | matches | selection */
    public string $order = 'nom';

    protected $queryString = [
        'search' => ['except' => ''],
        'team' => ['except' => 'bleus'],
        'country' => ['except' => ''],
        'position' => ['except' => ''],
        'order' => ['except' => 'nom'],
    ];

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'team', 'country', 'position', 'order'], true)) {
            $this->resetPage();
        }
    }

    public function updatedTeam(): void
    {
        $this->country = '';
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'country', 'position', 'order']);
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'partials.pagination';
    }

    public function render()
    {
        $franceId = Country::where('code', 'FRA')->value('id');

        $query = Player::with('country')->withCount('lineups');

        match ($this->team) {
            'adversaires' => $query->where('country_id', '!=', $franceId),
            'tous' => null,
            default => $query->where('country_id', $franceId),
        };

        if ($this->search !== '') {
            $search = $this->search;
            $query->where(fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('nickname', 'like', "%{$search}%"));
        }

        if ($this->country !== '' && $this->team !== 'bleus') {
            $query->where('country_id', $this->country);
        }

        if ($this->position !== '') {
            $query->where('primary_position', $this->position);
        }

        match ($this->order) {
            'matches' => $query->orderByDesc('lineups_count')->orderBy('last_name'),
            // Joueurs sans numéro de sélection en fin de liste
            'selection' => $query->orderByRaw('cap_number IS NULL')->orderBy('cap_number')->orderBy('last_name'),
            default => $query->orderBy('last_name')->orderBy('first_name'),
        };

        return view('livewire.player-list', [
            'players' => $query->paginate(48),
            'countries' => Country::where('id', '!=', $franceId)->whereHas('players')->orderBy('name')->get(),
            'positions' => PlayerPosition::cases(),
            'counts' => [
                'bleus' => Player::where('country_id', $franceId)->count(),
                'adversaires' => Player::where('country_id', '!=', $franceId)->count(),
            ],
            'filtered' => $this->search !== '' || $this->country !== '' || $this->position !== '' || $this->order !== 'nom',
        ])->layout('layouts.app');
    }
}
