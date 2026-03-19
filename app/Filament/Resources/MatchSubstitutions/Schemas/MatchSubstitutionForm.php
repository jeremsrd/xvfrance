<?php

namespace App\Filament\Resources\MatchSubstitutions\Schemas;

use App\Enums\TeamSide;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MatchSubstitutionForm
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
                Select::make('player_off_id')
                    ->label('Joueur sortant')
                    ->relationship('playerOff', 'last_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('player_on_id')
                    ->label('Joueur entrant')
                    ->relationship('playerOn', 'last_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('minute')
                    ->label('Minute')
                    ->required()
                    ->numeric(),
                Toggle::make('is_tactical')
                    ->label('Tactique')
                    ->default(true),
                Select::make('team_side')
                    ->label('Équipe')
                    ->options(TeamSide::class)
                    ->required(),
            ]);
    }
}
