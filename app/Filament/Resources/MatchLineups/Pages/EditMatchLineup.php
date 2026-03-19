<?php

namespace App\Filament\Resources\MatchLineups\Pages;

use App\Filament\Resources\MatchLineups\MatchLineupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMatchLineup extends EditRecord
{
    protected static string $resource = MatchLineupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
