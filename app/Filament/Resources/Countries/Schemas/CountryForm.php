<?php

namespace App\Filament\Resources\Countries\Schemas;

use App\Enums\Continent;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->required(),
                TextInput::make('code')
                    ->label('Code World Rugby')
                    ->required()
                    ->maxLength(3),
                Select::make('continent')
                    ->label('Continent')
                    ->options(Continent::class)
                    ->default(Continent::EUROPE->value)
                    ->required(),
                TextInput::make('flag_emoji')
                    ->label('Drapeau (emoji)'),
            ]);
    }
}
