<?php

namespace App\Filament\Resources\CoachTenures\Pages;

use App\Filament\Resources\CoachTenures\CoachTenureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCoachTenure extends EditRecord
{
    protected static string $resource = CoachTenureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
