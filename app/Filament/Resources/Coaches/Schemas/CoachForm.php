<?php

namespace App\Filament\Resources\Coaches\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CoachForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->label('Prénom')
                    ->required(),
                TextInput::make('last_name')
                    ->label('Nom')
                    ->required(),
                DatePicker::make('birth_date')
                    ->label('Date de naissance'),
                TextInput::make('birth_city')
                    ->label('Ville de naissance'),
                Select::make('country_id')
                    ->label('Nationalité')
                    ->relationship('country', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('photo_url')
                    ->label('Photo (URL)')
                    ->url(),
            ]);
    }
}
