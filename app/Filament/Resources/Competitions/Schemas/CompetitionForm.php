<?php

namespace App\Filament\Resources\Competitions\Schemas;

use App\Enums\CompetitionType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CompetitionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom')
                    ->required(),
                TextInput::make('short_name')
                    ->label('Nom court')
                    ->required(),
                Select::make('type')
                    ->label('Type')
                    ->options(CompetitionType::class)
                    ->required(),
            ]);
    }
}
