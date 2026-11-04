<?php

namespace App\Filament\Resources\Requisitions;

use App\Filament\Resources\Requisitions\Pages\CreateRequisition;
use App\Filament\Resources\Requisitions\Pages\EditRequisition;
use App\Filament\Resources\Requisitions\Pages\ListRequisitions;
use App\Filament\Resources\Requisitions\Schemas\RequisitionForm;
use App\Filament\Resources\Requisitions\Tables\RequisitionsTable;
use App\Models\Requisition;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RequisitionResource extends Resource
{
    protected static ?string $model = Requisition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $navigationLabel = 'All Requisitions';
    protected static ?int $navigationSort = 2;

    public static function canEdit(Model $record): bool
    {
        return $record->isEditable();
    }

    public static function form(Schema $schema): Schema
    {
        return RequisitionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RequisitionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRequisitions::route('/'),
            'create' => CreateRequisition::route('/create'),
            'edit' => EditRequisition::route('/{record}/edit'),
        ];
    }
}
