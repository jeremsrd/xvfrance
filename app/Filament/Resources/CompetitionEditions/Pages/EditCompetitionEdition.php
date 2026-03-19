<?php

namespace App\Filament\Resources\CompetitionEditions\Pages;

use App\Filament\Resources\CompetitionEditions\CompetitionEditionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompetitionEdition extends EditRecord
{
    protected static string $resource = CompetitionEditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
