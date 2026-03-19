<?php

namespace App\Filament\Resources\Coaches;

use App\Filament\Resources\Coaches\Pages\CreateCoach;
use App\Filament\Resources\Coaches\Pages\EditCoach;
use App\Filament\Resources\Coaches\Pages\ListCoaches;
use App\Filament\Resources\Coaches\RelationManagers\TenuresRelationManager;
use App\Filament\Resources\Coaches\Schemas\CoachForm;
use App\Filament\Resources\Coaches\Tables\CoachesTable;
use App\Models\Coach;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CoachResource extends Resource
{
    protected static ?string $model = Coach::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $modelLabel = 'Sélectionneur';

    protected static ?string $pluralModelLabel = 'Sélectionneurs';

    protected static ?string $navigationLabel = 'Sélectionneurs';

    protected static string|\UnitEnum|null $navigationGroup = 'Joueurs & Staff';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return CoachForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CoachesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TenuresRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoaches::route('/'),
            'create' => CreateCoach::route('/create'),
            'edit' => EditCoach::route('/{record}/edit'),
        ];
    }
}
