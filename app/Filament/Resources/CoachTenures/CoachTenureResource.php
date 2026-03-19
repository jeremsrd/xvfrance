<?php

namespace App\Filament\Resources\CoachTenures;

use App\Filament\Resources\CoachTenures\Pages\CreateCoachTenure;
use App\Filament\Resources\CoachTenures\Pages\EditCoachTenure;
use App\Filament\Resources\CoachTenures\Pages\ListCoachTenures;
use App\Filament\Resources\CoachTenures\Schemas\CoachTenureForm;
use App\Filament\Resources\CoachTenures\Tables\CoachTenuresTable;
use App\Models\CoachTenure;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CoachTenureResource extends Resource
{
    protected static ?string $model = CoachTenure::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'Mandat';

    protected static ?string $pluralModelLabel = 'Mandats';

    protected static ?string $navigationLabel = 'Mandats';

    protected static string|\UnitEnum|null $navigationGroup = 'Joueurs & Staff';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return CoachTenureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CoachTenuresTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoachTenures::route('/'),
            'create' => CreateCoachTenure::route('/create'),
            'edit' => EditCoachTenure::route('/{record}/edit'),
        ];
    }
}
