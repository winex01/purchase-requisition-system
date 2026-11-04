<?php

namespace App\Filament\Resources\Purchasings;

use App\Filament\Resources\Purchasings\Pages\ManagePurchasings;
use App\Models\PurchaseStatus;
use App\Models\Requisition;
use App\Models\RequisitionStatus;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PurchasingResource extends Resource
{
    protected static ?string $model = Requisition::class;

    protected static ?string $navigationLabel = 'Purchasing';
    protected static ?string $modelLabel = 'purchase';
    protected static ?string $pluralModelLabel = 'purchasing';
    protected static string|UnitEnum|null $navigationGroup = 'Workflow';
    protected static ?int $navigationSort = 2;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('status', fn (Builder $q) => $q->where('code', 'for_purchasing'))
            ->with(['employee', 'department', 'items.stockStatus']);
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
        $receivedId = PurchaseStatus::idOf('received');

        return $table
            ->columns([
                TextColumn::make('reference_no')->label('Ref No.')->searchable()->sortable(),
                TextColumn::make('employee.full_name')->label('Requested by'),
                TextColumn::make('department.name')->badge(),
                TextColumn::make('date_needed')->date()->sortable(),
                TextColumn::make('total')->money('PHP'),
                TextColumn::make('progress')
                    ->label('Items received')
                    ->state(fn (Requisition $record) => $record->items->where('purchase_status_id', $receivedId)->count()
                        . ' / ' . $record->items->count()),
            ])
            ->defaultSort('date_needed')
            ->recordActions([
                Action::make('update')
                    ->label('Update purchase')
                    ->icon(Heroicon::OutlinedShoppingCart)
                    ->modalWidth('5xl')
                    ->modalHeading(fn (Requisition $record) => "Purchasing — {$record->reference_no}")
                    ->fillForm(fn (Requisition $record) => [
                        'items' => $record->items->map(fn ($item) => [
                            'id' => $item->id,
                            'description' => $item->description,
                            'quantity' => $item->quantity,
                            'stock' => $item->stockStatus?->name,
                            'purchase_status_id' => $item->purchase_status_id,
                        ])->all(),
                    ])
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(5)
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('description')->disabled()->dehydrated(false)->columnSpan(2),
                                TextInput::make('quantity')->disabled()->dehydrated(false),
                                TextInput::make('stock')->label('Stock status')->disabled()->dehydrated(false),
                                Select::make('purchase_status_id')
                                    ->label('Purchase status')
                                    ->options(fn () => PurchaseStatus::orderBy('sort_order')->pluck('name', 'id'))
                                    ->required(),
                            ]),
                    ])
                    ->action(function (Requisition $record, array $data) use ($receivedId) {
                        foreach ($data['items'] as $row) {
                            $record->items()->whereKey($row['id'])
                                ->update(['purchase_status_id' => $row['purchase_status_id']]);
                        }

                        $allReceived = $record->items()
                            ->where('purchase_status_id', '!=', $receivedId)
                            ->doesntExist();

                        if ($allReceived) {
                            $record->update([
                                'requisition_status_id' => RequisitionStatus::idOf('received'),
                            ]);

                            Notification::make()
                                ->title("{$record->reference_no} fully received — moved to Records")
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()->title('Purchase status saved')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePurchasings::route('/'),
        ];
    }
}
