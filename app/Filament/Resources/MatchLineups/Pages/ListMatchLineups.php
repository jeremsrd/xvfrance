<?php

namespace App\Filament\Resources\MatchLineups\Pages;

use App\Filament\Resources\MatchLineups\MatchLineupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMatchLineups extends ListRecords
{
    protected static string $resource = MatchLineupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
