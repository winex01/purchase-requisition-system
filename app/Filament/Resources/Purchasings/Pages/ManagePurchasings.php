<?php

namespace App\Filament\Resources\Purchasings\Pages;

use App\Filament\Resources\Purchasings\PurchasingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePurchasings extends ManageRecords
{
    protected static string $resource = PurchasingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
