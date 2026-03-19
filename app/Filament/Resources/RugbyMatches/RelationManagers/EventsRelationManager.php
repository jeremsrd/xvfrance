<?php

namespace App\Filament\Resources\RugbyMatches\RelationManagers;

use App\Enums\EventType;
use App\Enums\TeamSide;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Événements';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('player_id')
                    ->label('Joueur')
                    ->relationship('player', 'last_name')
                    ->searchable()
                    ->preload(),
                Select::make('event_type')
                    ->label('Type')
                    ->options(EventType::class)
                    ->required(),
                TextInput::make('minute')
                    ->label('Minute')
                    ->numeric(),
                Select::make('team_side')
                    ->label('Équipe')
                    ->options(TeamSide::class)
                    ->required(),
                TextInput::make('detail')
                    ->label('Détail'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('minute')
                    ->label('Min.')
                    ->sortable(),
                TextColumn::make('player.last_name')
                    ->label('Joueur')
                    ->searchable(),
                TextColumn::make('event_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (EventType $state): string => match ($state) {
                        EventType::ESSAI => 'success',
                        EventType::ESSAI_PENALITE => 'success',
                        EventType::TRANSFORMATION => 'info',
                        EventType::PENALITE => 'info',
                        EventType::DROP => 'primary',
                        EventType::CARTON_JAUNE => 'warning',
                        EventType::CARTON_ROUGE => 'danger',
                    }),
                TextColumn::make('team_side')
                    ->label('Équipe')
                    ->badge(),
            ])
            ->defaultSort('minute')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
