<?php

namespace App\Filament\Resources\RugbyMatches\Pages;

use App\Filament\Resources\RugbyMatches\RugbyMatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRugbyMatches extends ListRecords
{
    protected static string $resource = RugbyMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
