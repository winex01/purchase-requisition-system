<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionHistory extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(RequisitionStatus::class, 'requisition_status_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
