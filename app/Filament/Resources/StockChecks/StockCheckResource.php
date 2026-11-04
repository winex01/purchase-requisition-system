<?php

namespace App\Filament\Resources\StockChecks;

use App\Filament\Resources\StockChecks\Pages\ManageStockChecks;
use App\Models\Requisition;
use App\Models\RequisitionStatus;
use App\Models\StockStatus;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class StockCheckResource extends Resource
{
    protected static ?string $model = Requisition::class;

    protected static ?string $navigationLabel = 'Stock Check';
    protected static ?string $modelLabel = 'stock check';
    protected static ?string $pluralModelLabel = 'stock checks';
    protected static string|UnitEnum|null $navigationGroup = 'Workflow';
    protected static ?int $navigationSort = 1;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('status', fn (Builder $q) => $q->where('code', 'stock_pending'))
            ->with(['employee', 'department', 'items']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count() ?: null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $uncheckedId = StockStatus::idOf('unchecked');

        return $table
            ->columns([
                TextColumn::make('reference_no')->label('Ref No.')->searchable()->sortable(),
                TextColumn::make('employee.full_name')->label('Requested by'),
                TextColumn::make('department.name')->badge(),
                TextColumn::make('date_needed')->date()->sortable(),
                TextColumn::make('total')->money('PHP'),
                TextColumn::make('progress')
                    ->label('Items checked')
                    ->state(fn (Requisition $record) => $record->items->where('stock_status_id', '!=', $uncheckedId)->count()
                        . ' / ' . $record->items->count()),
            ])
            ->defaultSort('date_needed')
            ->recordActions([
                Action::make('check')
                    ->label('Check stock')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->modalWidth('4xl')
                    ->modalHeading(fn (Requisition $record) => "Stock check — {$record->reference_no}")
                    ->fillForm(fn (Requisition $record) => [
                        'items' => $record->items->map(fn ($item) => [
                            'id' => $item->id,
                            'description' => $item->description,
                            'quantity' => $item->quantity,
                            'stock_status_id' => $item->stock_status_id,
                        ])->all(),
                    ])
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(4)
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('description')->disabled()->dehydrated(false)->columnSpan(2),
                                TextInput::make('quantity')->disabled()->dehydrated(false),
                                Select::make('stock_status_id')
                                    ->label('Stock status')
                                    ->options(fn () => StockStatus::orderBy('sort_order')->pluck('name', 'id'))
                                    ->required(),
                            ]),
                    ])
                    ->action(function (Requisition $record, array $data) {
                        foreach ($data['items'] as $row) {
                            $record->items()->whereKey($row['id'])
                                ->update(['stock_status_id' => $row['stock_status_id']]);
                        }

                        Notification::make()->title('Stock status saved')->success()->send();
                    }),

                Action::make('forward')
                    ->label('Forward to Purchaser')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Every item has a stock status. Send this request to the Purchaser?')
                    ->visible(fn (Requisition $record) => $record->items->isNotEmpty()
                        && ! $record->items->contains('stock_status_id', $uncheckedId))
                    ->action(function (Requisition $record) {
                        $record->moveTo('for_purchasing');

                        Notification::make()
                            ->title("{$record->reference_no} forwarded to Purchaser")
                            ->success()
                            ->send();
                    }),

                Action::make('return')
                    ->label('Return for revision')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('warning')
                    ->schema([
                        Textarea::make('note')
                            ->label('What needs to be changed?')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Requisition $record, array $data) {
                        $record->moveTo('returned', $data['note']);

                        Notification::make()
                            ->title("{$record->reference_no} returned to requester")
                            ->warning()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStockChecks::route('/'),
        ];
    }
}
