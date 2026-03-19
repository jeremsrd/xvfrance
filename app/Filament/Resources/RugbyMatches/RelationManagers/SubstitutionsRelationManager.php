<?php

namespace App\Filament\Resources\RugbyMatches\RelationManagers;

use App\Enums\TeamSide;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubstitutionsRelationManager extends RelationManager
{
    protected static string $relationship = 'substitutions';

    protected static ?string $title = 'Remplacements';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('player_off_id')
                    ->label('Joueur sortant')
                    ->relationship('playerOff', 'last_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('player_on_id')
                    ->label('Joueur entrant')
                    ->relationship('playerOn', 'last_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('minute')
                    ->label('Minute')
                    ->required()
                    ->numeric(),
                Toggle::make('is_tactical')
                    ->label('Tactique')
                    ->default(true),
                Select::make('team_side')
                    ->label('Équipe')
                    ->options(TeamSide::class)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('minute')
                    ->label('Min.')
                    ->sortable(),
                TextColumn::make('playerOff.last_name')
                    ->label('Sortant'),
                TextColumn::make('playerOn.last_name')
                    ->label('Entrant'),
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
