<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (RequisitionItem $item) {
            $item->stock_status_id ??= StockStatus::idOf('unchecked');
            $item->purchase_status_id ??= PurchaseStatus::idOf('pending');
        });
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public function stockStatus(): BelongsTo
    {
        return $this->belongsTo(StockStatus::class);
    }

    public function purchaseStatus(): BelongsTo
    {
        return $this->belongsTo(PurchaseStatus::class);
    }

    protected function subtotal(): Attribute
    {
        return Attribute::get(fn () => (float) $this->quantity * (float) $this->unit_price);
    }
}
