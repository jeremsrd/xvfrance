<?php

namespace App\Filament\Resources\MatchSubstitutions\Pages;

use App\Filament\Resources\MatchSubstitutions\MatchSubstitutionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMatchSubstitutions extends ListRecords
{
    protected static string $resource = MatchSubstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
