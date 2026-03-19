<?php

namespace App\Filament\Resources\MatchSubstitutions\Pages;

use App\Filament\Resources\MatchSubstitutions\MatchSubstitutionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMatchSubstitution extends EditRecord
{
    protected static string $resource = MatchSubstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
