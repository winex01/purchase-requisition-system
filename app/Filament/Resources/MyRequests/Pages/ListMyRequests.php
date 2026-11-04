<?php

namespace App\Filament\Resources\MyRequests\Pages;

use App\Filament\Resources\MyRequests\MyRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMyRequests extends ListRecords
{
    protected static string $resource = MyRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
