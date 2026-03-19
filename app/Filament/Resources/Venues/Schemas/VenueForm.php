<?php

namespace App\Filament\Resources\Venues\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VenueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->required(),
                TextInput::make('city')
                    ->label('Ville')
                    ->required(),
                Select::make('country_id')
                    ->label('Pays')
                    ->relationship('country', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('capacity')
                    ->label('Capacité')
                    ->numeric(),
                TextInput::make('opened_year')
                    ->label('Année d\'ouverture')
                    ->numeric(),
                TextInput::make('latitude')
                    ->label('Latitude')
                    ->numeric(),
                TextInput::make('longitude')
                    ->label('Longitude')
                    ->numeric(),
            ]);
    }
}
