<?php

namespace App\Filament\Resources\StockChecks\Pages;

use App\Filament\Resources\StockChecks\StockCheckResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStockChecks extends ManageRecords
{
    protected static string $resource = StockCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
