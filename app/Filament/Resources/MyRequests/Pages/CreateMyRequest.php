<?php

namespace App\Filament\Resources\MyRequests\Pages;

use App\Filament\Resources\MyRequests\MyRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMyRequest extends CreateRecord
{
    protected static string $resource = MyRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $employee = auth()->user()->employee;

        $data['employee_id'] = $employee->id;
        $data['department_id'] = $employee->department_id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}