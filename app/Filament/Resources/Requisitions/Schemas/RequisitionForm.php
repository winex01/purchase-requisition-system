<?php

namespace App\Filament\Resources\Requisitions\Schemas;

use App\Models\Employee;
use App\Models\Requisition;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class RequisitionForm
{
    public static function configure(Schema $schema, bool $forOwner = false): Schema
    {
        return $schema->components([
            Section::make('Request details')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('employee_id')
                        ->hidden($forOwner)
                        ->label('Requested by')
                        ->relationship('employee', 'last_name')
                        ->getOptionLabelFromRecordUsing(fn (Employee $record) => $record->full_name)
                        ->searchable(['first_name', 'last_name'])
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn ($state, Set $set) => $set(
                            'department_id',
                            Employee::find($state)?->department_id
                        )),
                    Select::make('department_id')
                        ->hidden($forOwner)
                        ->label('Department')
                        ->relationship('department', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    DatePicker::make('date_filed')
                        ->label('Date of filing')
                        ->default(now())
                        ->required(),
                    DatePicker::make('date_needed')
                        ->required()
                        ->afterOrEqual('date_filed'),
                    Textarea::make('remarks')
                        ->label('Remarks / Purpose')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Items requested')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->hiddenLabel()
                        ->minItems(1)
                        ->defaultItems(1)
                        ->addActionLabel('Add another item')
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => $set(
                            'grand_total',
                            self::grandTotal($get('items') ?? [])
                        ))
                        ->columns(5)
                        ->schema([
                            TextInput::make('description')
                                ->label('Item description')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2),
                            TextInput::make('quantity')
                                ->numeric()
                                ->minValue(0.01)
                                ->default(1)
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::refreshTotals($get, $set)),
                            TextInput::make('unit_price')
                                ->label('Unit price')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('₱')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::refreshTotals($get, $set)),
                            TextInput::make('line_total')
                                ->label('Total')
                                ->prefix('₱')
                                ->disabled()
                                ->dehydrated(false)
                                ->afterStateHydrated(fn (Get $get, Set $set) => $set(
                                    'line_total',
                                    self::lineTotal($get('quantity'), $get('unit_price'))
                                )),
                        ]),

                    TextInput::make('grand_total')
                        ->label('Total cost of all items')
                        ->prefix('₱')
                        ->disabled()
                        ->dehydrated(false)
                        ->default('0.00')
                        ->formatStateUsing(fn (?Requisition $record) => $record
                            ? number_format($record->total, 2, '.', '')
                            : '0.00'),
                ]),
        ]);
    }

    /** Runs inside a repeater row: updates this row's total and the grand total. */
    protected static function refreshTotals(Get $get, Set $set): void
    {
        $set('line_total', self::lineTotal($get('quantity'), $get('unit_price')));
        $set('../../grand_total', self::grandTotal($get('../../items') ?? []));
    }

    protected static function lineTotal(mixed $quantity, mixed $unitPrice): string
    {
        return number_format((float) $quantity * (float) $unitPrice, 2, '.', '');
    }

    protected static function grandTotal(array $items): string
    {
        return number_format(
            collect($items)->sum(fn ($item) => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)),
            2, '.', ''
        );
    }
}
