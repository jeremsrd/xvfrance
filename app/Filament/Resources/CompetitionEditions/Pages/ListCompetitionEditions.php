<?php

namespace App\Filament\Resources\CompetitionEditions\Pages;

use App\Filament\Resources\CompetitionEditions\CompetitionEditionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompetitionEditions extends ListRecords
{
    protected static string $resource = CompetitionEditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
