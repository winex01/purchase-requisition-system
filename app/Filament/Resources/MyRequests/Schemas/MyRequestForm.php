<?php

namespace App\Filament\Resources\MyRequests\Schemas;

use Filament\Schemas\Schema;

class MyRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }
}
