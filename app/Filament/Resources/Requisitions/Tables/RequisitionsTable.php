<?php

namespace App\Filament\Resources\Requisitions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RequisitionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['employee', 'department', 'status', 'items']))
            ->columns([
                TextColumn::make('reference_no')
                    ->label('Ref No.')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.full_name')
                    ->label('Requested by')
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas(
                        'employee',
                        fn (Builder $q) => $q
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                    )),
                TextColumn::make('department.name')
                    ->badge()
                    ->sortable(),
                TextColumn::make('date_needed')
                    ->date()
                    ->sortable(),
                TextColumn::make('total')
                    ->money('PHP'),
                TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => $record->status->color),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('requisition_status_id')
                    ->label('Status')
                    ->relationship('status', 'name'),
                SelectFilter::make('department')
                    ->relationship('department', 'name')
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