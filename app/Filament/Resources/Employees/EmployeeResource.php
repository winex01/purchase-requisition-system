<?php

namespace App\Filament\Resources\Employees;

use App\Filament\Resources\Employees\Pages\ManageEmployees;
use App\Models\Employee;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('middle_name')
                    ->maxLength(255),
                TextInput::make('last_name')
                    ->required()
                    ->maxLength(255),
                Select::make('suffix')
                    ->options([
                        'Jr.' => 'Jr.',
                        'Sr.' => 'Sr.',
                        'I' => 'I',
                        'II'  => 'II',
                        'III' => 'III',
                        'IV'  => 'IV',
                        'V'  => 'V',
                    ])
                    ->placeholder('None'),
                Select::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->unique('departments', 'name'),
                    ]),
                TextInput::make('position')
                    ->maxLength(255),
                Select::make('user_id')
                    ->label('User account (optional)')
                    ->relationship(
                        name: 'user',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query, ?Employee $record) => $query
                            ->whereDoesntHave('employee', fn (Builder $q) => $q
                                ->when($record, fn (Builder $q) => $q->where('id', '!=', $record->id))),
                    )
                    ->searchable()
                    ->preload()
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_name')
            ->columns([
                TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'middle_name', 'last_name'])
                    ->sortable(['last_name', 'first_name']),
                TextColumn::make('department.name')
                    ->badge()
                    ->sortable(),
                TextColumn::make('position')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('Login')
                    ->placeholder('No account')
                    ->toggleable(),
            ])
            ->filters([
                 SelectFilter::make('department')
                    ->relationship('department', 'name')
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEmployees::route('/'),
        ];
    }
}
