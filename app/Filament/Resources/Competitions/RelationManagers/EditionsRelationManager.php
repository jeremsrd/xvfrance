<?php

namespace App\Filament\Resources\Competitions\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EditionsRelationManager extends RelationManager
{
    protected static string $relationship = 'editions';

    protected static ?string $title = 'Éditions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('year')
                    ->label('Année')
                    ->required()
                    ->numeric(),
                TextInput::make('label')
                    ->label('Libellé')
                    ->required(),
                TextInput::make('france_ranking')
                    ->label('Classement France')
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('year')
                    ->label('Année')
                    ->sortable(),
                TextColumn::make('label')
                    ->label('Libellé')
                    ->searchable(),
                TextColumn::make('france_ranking')
                    ->label('Classement France')
                    ->sortable(),
            ])
            ->defaultSort('year', 'desc')
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
