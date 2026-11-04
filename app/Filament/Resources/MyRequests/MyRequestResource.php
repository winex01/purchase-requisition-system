<?php

namespace App\Filament\Resources\MyRequests;

use App\Filament\Resources\MyRequests\Pages\CreateMyRequest;
use App\Filament\Resources\MyRequests\Pages\EditMyRequest;
use App\Filament\Resources\MyRequests\Pages\ListMyRequests;
use App\Filament\Resources\Requisitions\Schemas\RequisitionForm;
use App\Models\Requisition;
use App\Models\StockStatus;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MyRequestResource extends Resource
{
    protected static ?string $model = Requisition::class;

    protected static ?string $navigationLabel = 'My Requests';
    protected static ?string $modelLabel = 'request';
    protected static ?string $pluralModelLabel = 'my requests';
    protected static ?int $navigationSort = 1;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('employee_id', auth()->user()?->employee?->id ?? 0)
            ->with(['status', 'items', 'latestHistory']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->employee !== null;
    }

    public static function canEdit(Model $record): bool
    {
        return $record->isEditable();
    }

    public static function form(Schema $schema): Schema
    {
        return RequisitionForm::configure($schema, forOwner: true);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_no')->label('Ref No.')->searchable()->sortable(),
                TextColumn::make('date_needed')->date()->sortable(),
                TextColumn::make('total')->money('PHP'),
                TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Requisition $record) => $record->status->color),
                TextColumn::make('latestHistory.note')
                    ->label('Latest note')
                    ->limit(50)
                    ->tooltip(fn (Requisition $record) => $record->latestHistory?->note)
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('requisition_status_id')
                    ->label('Status')
                    ->relationship('status', 'name'),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('resubmit')
                    ->label('Resubmit')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('This sends the request back to Stock Check, and all items will be checked again.')
                    ->visible(fn (Requisition $record) => $record->status->code === 'returned')
                    ->action(function (Requisition $record) {
                        $record->items()->update([
                            'stock_status_id' => StockStatus::idOf('unchecked'),
                        ]);
                        $record->moveTo('stock_pending', 'Revised and resubmitted');

                        Notification::make()
                            ->title("{$record->reference_no} resubmitted")
                            ->success()
                            ->send();
                    }),

                Action::make('cancel')
                    ->label('Cancel request')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (Requisition $record) => $record->isCancellable())
                    ->schema([
                        Textarea::make('reason')
                            ->label('Reason for cancelling')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Requisition $record, array $data) {
                        $record->moveTo('cancelled', $data['reason']);

                        Notification::make()
                            ->title("{$record->reference_no} cancelled")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMyRequests::route('/'),
            'create' => CreateMyRequest::route('/create'),
            'edit' => EditMyRequest::route('/{record}/edit'),
        ];
    }
}