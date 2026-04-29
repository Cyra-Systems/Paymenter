<?php

namespace Paymenter\Extensions\Others\Blog\Admin\Resources\BlogPostResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FeedbackRelationManager extends RelationManager
{
    protected static string $relationship = 'feedback';

    protected static ?string $title = 'Feedback';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('is_helpful')
                    ->label('Helpful')
                    ->boolean(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('Guest')
                    ->searchable(),
                TagsColumn::make('reasons')
                    ->label('Reasons')
                    ->limitList(3)
                    ->badge(),
                TextColumn::make('note')
                    ->label('Note')
                    ->limit(90)
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->since()
                    ->sortable()
                    ->dateTimeTooltip(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('session_id')
                    ->label('Session')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
