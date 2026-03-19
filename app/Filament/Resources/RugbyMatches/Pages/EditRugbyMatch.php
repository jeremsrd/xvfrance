<?php

namespace App\Filament\Resources\RugbyMatches\Pages;

use App\Filament\Resources\RugbyMatches\RugbyMatchResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRugbyMatch extends EditRecord
{
    protected static string $resource = RugbyMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
