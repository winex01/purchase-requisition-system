<?php

namespace App\Models;

use App\Models\Concerns\HasIdOf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseStatus extends Model
{
    use HasIdOf;
    protected $guarded = [];

    public function items(): HasMany
    {
        return $this->hasMany(RequisitionItem::class);
    }
}
