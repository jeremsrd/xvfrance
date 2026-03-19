<?php

namespace App\Filament\Resources\MatchEvents\Pages;

use App\Filament\Resources\MatchEvents\MatchEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMatchEvents extends ListRecords
{
    protected static string $resource = MatchEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
