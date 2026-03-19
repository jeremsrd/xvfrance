<?php

namespace App\Filament\Resources\MatchSubstitutions;

use App\Filament\Resources\MatchSubstitutions\Pages\CreateMatchSubstitution;
use App\Filament\Resources\MatchSubstitutions\Pages\EditMatchSubstitution;
use App\Filament\Resources\MatchSubstitutions\Pages\ListMatchSubstitutions;
use App\Filament\Resources\MatchSubstitutions\Schemas\MatchSubstitutionForm;
use App\Filament\Resources\MatchSubstitutions\Tables\MatchSubstitutionsTable;
use App\Models\MatchSubstitution;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MatchSubstitutionResource extends Resource
{
    protected static ?string $model = MatchSubstitution::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $modelLabel = 'Remplacement';

    protected static ?string $pluralModelLabel = 'Remplacements';

    protected static ?string $navigationLabel = 'Remplacements';

    protected static string|\UnitEnum|null $navigationGroup = 'Détails match';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return MatchSubstitutionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MatchSubstitutionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMatchSubstitutions::route('/'),
            'create' => CreateMatchSubstitution::route('/create'),
            'edit' => EditMatchSubstitution::route('/{record}/edit'),
        ];
    }
}
