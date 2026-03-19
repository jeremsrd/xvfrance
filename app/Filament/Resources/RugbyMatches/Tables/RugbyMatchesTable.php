<?php

namespace App\Filament\Resources\RugbyMatches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RugbyMatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('match_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('opponent.name')
                    ->label('Adversaire')
                    ->searchable()
                    ->formatStateUsing(fn ($record) => ($record->opponent->flag_emoji ?? '') . ' ' . $record->opponent->name),
                TextColumn::make('france_score')
                    ->label('France')
                    ->sortable(),
                TextColumn::make('opponent_score')
                    ->label('Adv.')
                    ->sortable(),
                TextColumn::make('result')
                    ->label('Résultat')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->result)
                    ->color(fn (string $state): string => match ($state) {
                        'Victoire' => 'success',
                        'Défaite' => 'danger',
                        'Nul' => 'warning',
                    }),
                TextColumn::make('venue.name')
                    ->label('Stade')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('edition.label')
                    ->label('Compétition')
                    ->toggleable(),
                TextColumn::make('stage')
                    ->label('Phase')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('match_date', 'desc')
            ->filters([
                SelectFilter::make('opponent_id')
                    ->label('Adversaire')
                    ->relationship('opponent', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('stage')
                    ->label('Phase')
                    ->options(\App\Enums\MatchStage::class),
                SelectFilter::make('edition_id')
                    ->label('Compétition')
                    ->relationship('edition', 'label')
                    ->searchable()
                    ->preload(),
            ])
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
