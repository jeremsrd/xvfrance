<?php

namespace App\Filament\Resources\CompetitionEditions\Schemas;

use App\Models\Competition;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CompetitionEditionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('competition_id')
                    ->label('Compétition')
                    ->relationship('competition', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        static::updateLabel($get, $set);
                    })
                    ->live(),
                TextInput::make('year')
                    ->label('Année')
                    ->required()
                    ->numeric()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        static::updateLabel($get, $set);
                    })
                    ->live(onBlur: true),
                TextInput::make('label')
                    ->label('Libellé')
                    ->required(),
                TextInput::make('france_ranking')
                    ->label('Classement France')
                    ->numeric(),
            ]);
    }

    protected static function updateLabel(Get $get, Set $set): void
    {
        $competitionId = $get('competition_id');
        $year = $get('year');

        if ($competitionId && $year) {
            $competition = Competition::find($competitionId);
            if ($competition) {
                $set('label', $competition->short_name . ' ' . $year);
            }
        }
    }
}
