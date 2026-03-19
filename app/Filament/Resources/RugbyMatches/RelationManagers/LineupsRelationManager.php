<?php

namespace App\Filament\Resources\RugbyMatches\RelationManagers;

use App\Enums\PlayerPosition;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LineupsRelationManager extends RelationManager
{
    protected static string $relationship = 'lineups';

    protected static ?string $title = 'Compositions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('player_id')
                    ->label('Joueur')
                    ->relationship('player', 'last_name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('jersey_number')
                    ->label('N° maillot')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(23),
                Toggle::make('is_starter')
                    ->label('Titulaire')
                    ->default(true),
                Select::make('position_played')
                    ->label('Poste joué')
                    ->options(PlayerPosition::class)
                    ->required(),
                Toggle::make('is_captain')
                    ->label('Capitaine')
                    ->default(false),
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
                TextColumn::make('jersey_number')
                    ->label('N°')
                    ->sortable(),
                TextColumn::make('player.last_name')
                    ->label('Joueur')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position_played')
                    ->label('Poste')
                    ->badge(),
                IconColumn::make('is_starter')
                    ->label('Tit.')
                    ->boolean(),
                IconColumn::make('is_captain')
                    ->label('Cap.')
                    ->boolean(),
                TextColumn::make('team_side')
                    ->label('Équipe')
                    ->badge(),
            ])
            ->defaultSort('jersey_number')
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
