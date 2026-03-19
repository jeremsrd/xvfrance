<?php

namespace App\Filament\Resources\MatchLineups;

use App\Filament\Resources\MatchLineups\Pages\CreateMatchLineup;
use App\Filament\Resources\MatchLineups\Pages\EditMatchLineup;
use App\Filament\Resources\MatchLineups\Pages\ListMatchLineups;
use App\Filament\Resources\MatchLineups\Schemas\MatchLineupForm;
use App\Filament\Resources\MatchLineups\Tables\MatchLineupsTable;
use App\Models\MatchLineup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MatchLineupResource extends Resource
{
    protected static ?string $model = MatchLineup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'Composition';

    protected static ?string $pluralModelLabel = 'Compositions';

    protected static ?string $navigationLabel = 'Compositions';

    protected static string|\UnitEnum|null $navigationGroup = 'Détails match';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return MatchLineupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MatchLineupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMatchLineups::route('/'),
            'create' => CreateMatchLineup::route('/create'),
            'edit' => EditMatchLineup::route('/{record}/edit'),
        ];
    }
}
