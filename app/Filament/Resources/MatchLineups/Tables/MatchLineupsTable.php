<?php

namespace App\Filament\Resources\MatchLineups\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MatchLineupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('match.match_date')
                    ->label('Match')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('player.last_name')
                    ->label('Joueur')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jersey_number')
                    ->label('N°')
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
