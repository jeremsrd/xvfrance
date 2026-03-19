<?php

namespace App\Filament\Resources\CoachTenures\Schemas;

use App\Enums\CoachRole;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class CoachTenureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('coach_id')
                    ->label('Sélectionneur')
                    ->relationship('coach', 'last_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('role')
                    ->label('Rôle')
                    ->options(CoachRole::class)
                    ->default(CoachRole::SELECTIONNEUR->value)
                    ->required(),
                DatePicker::make('start_date')
                    ->label('Date de début')
                    ->required(),
                DatePicker::make('end_date')
                    ->label('Date de fin'),
            ]);
    }
}
