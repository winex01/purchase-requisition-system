<?php

namespace App\Filament\Resources\MyRequests\Pages;

use App\Filament\Resources\MyRequests\MyRequestResource;
use Filament\Resources\Pages\EditRecord;

class EditMyRequest extends EditRecord
{
    protected static string $resource = MyRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('index');
    }
}