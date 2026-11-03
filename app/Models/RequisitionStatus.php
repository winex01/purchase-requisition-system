<?php

namespace App\Models;

use App\Models\Concerns\HasIdOf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequisitionStatus extends Model
{
    use HasIdOf;
    protected $guarded = [];

    public function requisitions(): HasMany
    {
        return $this->hasMany(Requisition::class);
    }
}
