<?php

namespace App\Filament\Resources\MatchEvents\Pages;

use App\Filament\Resources\MatchEvents\MatchEventResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMatchEvent extends EditRecord
{
    protected static string $resource = MatchEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
