<?php

namespace App\Filament\Resources\MatchEvents\Tables;

use App\Enums\EventType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MatchEventsTable
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
