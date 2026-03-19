<?php

namespace App\Filament\Resources\CompetitionEditions;

use App\Filament\Resources\CompetitionEditions\Pages\CreateCompetitionEdition;
use App\Filament\Resources\CompetitionEditions\Pages\EditCompetitionEdition;
use App\Filament\Resources\CompetitionEditions\Pages\ListCompetitionEditions;
use App\Filament\Resources\CompetitionEditions\Schemas\CompetitionEditionForm;
use App\Filament\Resources\CompetitionEditions\Tables\CompetitionEditionsTable;
use App\Models\CompetitionEdition;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CompetitionEditionResource extends Resource
{
    protected static ?string $model = CompetitionEdition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $modelLabel = 'Édition';

    protected static ?string $pluralModelLabel = 'Éditions';

    protected static ?string $navigationLabel = 'Éditions';

    protected static string|\UnitEnum|null $navigationGroup = 'Compétitions';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return CompetitionEditionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompetitionEditionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompetitionEditions::route('/'),
            'create' => CreateCompetitionEdition::route('/create'),
            'edit' => EditCompetitionEdition::route('/{record}/edit'),
        ];
    }
}
