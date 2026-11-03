<?php

namespace App\Models\Concerns;

trait HasIdOf
{
    public static function idOf(string $code): int
    {
        return static::where('code', $code)->value('id')
            ?? throw new \RuntimeException("Status [{$code}] not found. Run the StatusSeeder.");
    }
}
