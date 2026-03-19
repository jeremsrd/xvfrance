<?php

namespace App\Filament\Resources\Players\Schemas;

use App\Enums\PlayerPosition;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PlayerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identité')
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')
                            ->label('Prénom')
                            ->required(),
                        TextInput::make('last_name')
                            ->label('Nom')
                            ->required(),
                        TextInput::make('nickname')
                            ->label('Surnom')
                            ->maxLength(100),
                        DatePicker::make('birth_date')
                            ->label('Date de naissance'),
                        DatePicker::make('death_date')
                            ->label('Date de décès'),
                        TextInput::make('birth_city')
                            ->label('Ville de naissance'),
                        Select::make('birth_country_id')
                            ->label('Pays de naissance')
                            ->relationship('birthCountry', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('country_id')
                            ->label('Sélection nationale')
                            ->relationship('country', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                Section::make('Profil rugby')
                    ->columns(2)
                    ->schema([
                        TextInput::make('cap_number')
                            ->label("N° d'international")
                            ->helperText('Numéro chronologique de sélection')
                            ->numeric(),
                        Select::make('primary_position')
                            ->label('Poste principal')
                            ->options(PlayerPosition::class)
                            ->required(),
                        TextInput::make('height_cm')
                            ->label('Taille')
                            ->numeric()
                            ->suffix('cm'),
                        TextInput::make('weight_kg')
                            ->label('Poids')
                            ->numeric()
                            ->suffix('kg'),
                        Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ]),

                Section::make('Photo')
                    ->schema([
                        FileUpload::make('photo_path')
                            ->label('Photo')
                            ->image()
                            ->directory('players')
                            ->maxSize(2048)
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('400')
                            ->imageResizeTargetHeight('400'),
                    ]),
            ]);
    }
}
