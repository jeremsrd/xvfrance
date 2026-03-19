<?php

namespace App\Filament\Resources\MatchEvents\Schemas;

use App\Enums\EventType;
use App\Enums\TeamSide;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MatchEventForm
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
                    ->preload(),
                Select::make('event_type')
                    ->label('Type')
                    ->options(EventType::class)
                    ->required(),
                TextInput::make('minute')
                    ->label('Minute')
                    ->numeric(),
                Select::make('team_side')
                    ->label('Équipe')
                    ->options(TeamSide::class)
                    ->required(),
                TextInput::make('detail')
                    ->label('Détail'),
            ]);
    }
}
