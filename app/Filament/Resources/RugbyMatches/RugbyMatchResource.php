<?php

namespace App\Filament\Resources\RugbyMatches;

use App\Filament\Resources\RugbyMatches\Pages\CreateRugbyMatch;
use App\Filament\Resources\RugbyMatches\Pages\EditRugbyMatch;
use App\Filament\Resources\RugbyMatches\Pages\ListRugbyMatches;
use App\Filament\Resources\RugbyMatches\RelationManagers\EventsRelationManager;
use App\Filament\Resources\RugbyMatches\RelationManagers\LineupsRelationManager;
use App\Filament\Resources\RugbyMatches\RelationManagers\SubstitutionsRelationManager;
use App\Filament\Resources\RugbyMatches\Schemas\RugbyMatchForm;
use App\Filament\Resources\RugbyMatches\Tables\RugbyMatchesTable;
use App\Models\RugbyMatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RugbyMatchResource extends Resource
{
    protected static ?string $model = RugbyMatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $modelLabel = 'Match';

    protected static ?string $pluralModelLabel = 'Matches';

    protected static ?string $navigationLabel = 'Matches';

    protected static string|\UnitEnum|null $navigationGroup = 'Matches';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return RugbyMatchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RugbyMatchesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LineupsRelationManager::class,
            EventsRelationManager::class,
            SubstitutionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRugbyMatches::route('/'),
            'create' => CreateRugbyMatch::route('/create'),
            'edit' => EditRugbyMatch::route('/{record}/edit'),
        ];
    }
}
