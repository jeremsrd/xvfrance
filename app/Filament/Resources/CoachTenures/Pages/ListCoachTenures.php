<?php

namespace App\Filament\Resources\CoachTenures\Pages;

use App\Filament\Resources\CoachTenures\CoachTenureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCoachTenures extends ListRecords
{
    protected static string $resource = CoachTenureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
