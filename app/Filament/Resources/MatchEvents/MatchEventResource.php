<?php

namespace App\Filament\Resources\MatchEvents;

use App\Filament\Resources\MatchEvents\Pages\CreateMatchEvent;
use App\Filament\Resources\MatchEvents\Pages\EditMatchEvent;
use App\Filament\Resources\MatchEvents\Pages\ListMatchEvents;
use App\Filament\Resources\MatchEvents\Schemas\MatchEventForm;
use App\Filament\Resources\MatchEvents\Tables\MatchEventsTable;
use App\Models\MatchEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MatchEventResource extends Resource
{
    protected static ?string $model = MatchEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $modelLabel = 'Événement';

    protected static ?string $pluralModelLabel = 'Événements';

    protected static ?string $navigationLabel = 'Événements';

    protected static string|\UnitEnum|null $navigationGroup = 'Détails match';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return MatchEventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MatchEventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMatchEvents::route('/'),
            'create' => CreateMatchEvent::route('/create'),
            'edit' => EditMatchEvent::route('/{record}/edit'),
        ];
    }
}
