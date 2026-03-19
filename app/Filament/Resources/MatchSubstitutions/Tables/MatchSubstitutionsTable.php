<?php

namespace App\Filament\Resources\MatchSubstitutions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MatchSubstitutionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('match.match_date')
                    ->label('Match')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('playerOff.last_name')
                    ->label('Sortant')
                    ->searchable(),
                TextColumn::make('playerOn.last_name')
                    ->label('Entrant')
                    ->searchable(),
                TextColumn::make('minute')
                    ->label('Min.')
                    ->sortable(),
                TextColumn::make('team_side')
                    ->label('Équipe')
                    ->badge(),
            ])
            ->defaultSort('minute')
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
