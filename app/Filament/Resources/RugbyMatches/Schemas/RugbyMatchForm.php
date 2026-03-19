<?php

namespace App\Filament\Resources\RugbyMatches\Schemas;

use App\Enums\MatchStage;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RugbyMatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations générales')
                    ->schema([
                        DatePicker::make('match_date')
                            ->label('Date du match')
                            ->required(),
                        TimePicker::make('kickoff_time')
                            ->label('Heure de coup d\'envoi'),
                        Select::make('venue_id')
                            ->label('Stade')
                            ->relationship('venue', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('opponent_id')
                            ->label('Adversaire')
                            ->relationship('opponent', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('edition_id')
                            ->label('Édition')
                            ->relationship('edition', 'label')
                            ->searchable()
                            ->preload(),
                        Toggle::make('is_home')
                            ->label('À domicile')
                            ->default(true),
                        Toggle::make('is_neutral')
                            ->label('Terrain neutre')
                            ->default(false),
                    ]),
                Section::make('Score')
                    ->schema([
                        TextInput::make('france_score')
                            ->label('Score France')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('opponent_score')
                            ->label('Score adversaire')
                            ->required()
                            ->numeric()
                            ->default(0),
                    ])->columns(2),
                Section::make('Détails')
                    ->schema([
                        Select::make('stage')
                            ->label('Phase')
                            ->options(MatchStage::class),
                        TextInput::make('match_number')
                            ->label('Numéro de match')
                            ->numeric(),
                        TextInput::make('attendance')
                            ->label('Affluence')
                            ->numeric(),
                        TextInput::make('referee')
                            ->label('Arbitre'),
                        Select::make('referee_country_id')
                            ->label('Pays de l\'arbitre')
                            ->relationship('refereeCountry', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('weather')
                            ->label('Météo'),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
