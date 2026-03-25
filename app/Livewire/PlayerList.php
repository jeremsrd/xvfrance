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
    public string $country = '';
    public string $position = '';
    public string $status = '';
    public string $sortField = 'last_name';
    public string $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'country' => ['except' => ''],
        'position' => ['except' => ''],
        'status' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCountry()
    {
        $this->resetPage();
    }

    public function updatingPosition()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function sort(string $field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = $field === 'last_name' ? 'asc' : 'desc';
        }
    }

    public function render()
    {
        $query = Player::with('country')
            ->withCount('lineups');

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($this->country) {
            $query->where('country_id', $this->country);
        }

        if ($this->position) {
            $query->where('primary_position', $this->position);
        }

        if ($this->status === 'actif') {
            $query->where('is_active', true);
        } elseif ($this->status === 'retraite') {
            $query->where('is_active', false);
        }

        $totalCount = $query->count();

        $sortField = $this->sortField;
        if ($sortField === 'selections') {
            $query->orderBy('lineups_count', $this->sortDirection);
        } else {
            $query->orderBy($sortField, $this->sortDirection);
        }

        $players = $query->paginate(30);

        $countries = Country::whereHas('players')
            ->orderBy('name')
            ->get();

        $positions = PlayerPosition::cases();

        return view('livewire.player-list', [
            'players' => $players,
            'countries' => $countries,
            'positions' => $positions,
            'totalCount' => $totalCount,
        ])->layout('layouts.app', [
            'title' => 'Joueurs du XV de France',
        ]);
    }
}
