<?php

namespace Paymenter\Extensions\Others\Upsells\Admin\Resources\Upsells\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UpsellsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'upgrade' => 'Product Upgrade',
                        'cross_sell' => 'Often ordered with',
                        'spend_minimum' => 'Spend minimum',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'upgrade' => 'success',
                        'cross_sell' => 'info',
                        'spend_minimum' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('triggerProduct.name')
                    ->label('Trigger Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('targetProduct.name')
                    ->label('Target Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Title')
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('priority')
                    ->label('Priority')
                    ->sortable(),

                IconColumn::make('enabled')
                    ->label('Enabled')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('priority', 'desc');
    }
}

