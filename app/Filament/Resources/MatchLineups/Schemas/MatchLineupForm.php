<?php

namespace App\Filament\Resources\MatchLineups\Schemas;

use App\Enums\PlayerPosition;
use App\Enums\TeamSide;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MatchLineupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('match_id')
                    ->label('Match')
                    ->relationship('match', 'match_date')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('player_id')
                    ->label('Joueur')
                    ->relationship('player', 'last_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('jersey_number')
                    ->label('Numéro de maillot')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(23),
                Toggle::make('is_starter')
                    ->label('Titulaire')
                    ->default(true),
                Select::make('position_played')
                    ->label('Poste joué')
                    ->options(PlayerPosition::class)
                    ->required(),
                Toggle::make('is_captain')
                    ->label('Capitaine')
                    ->default(false),
                Select::make('team_side')
                    ->label('Équipe')
                    ->options(TeamSide::class)
                    ->required(),
            ]);
    }
}
