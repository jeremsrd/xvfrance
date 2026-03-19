<?php

namespace App\Filament\Resources\Players\Tables;

use App\Enums\PlayerPosition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PlayersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(fn () => null),
                TextColumn::make('cap_number')
                    ->label('N°')
                    ->sortable(),
                TextColumn::make('last_name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('first_name')
                    ->label('Prénom')
                    ->searchable(),
                TextColumn::make('country.flag_emoji')
                    ->label('Pays'),
                TextColumn::make('primary_position')
                    ->label('Poste')
                    ->badge(),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                TextColumn::make('death_date')
                    ->label('Décès')
                    ->date('d/m/Y')
                    ->formatStateUsing(fn ($state) => $state ? '† ' . $state->format('d/m/Y') : '')
                    ->placeholder(''),
            ])
            ->defaultSort('last_name')
            ->filters([
                SelectFilter::make('country_id')
                    ->label('Pays')
                    ->relationship('country', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('primary_position')
                    ->label('Poste')
                    ->options(PlayerPosition::class),
                TernaryFilter::make('is_active')
                    ->label('Actif'),
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
