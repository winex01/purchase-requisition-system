<?php

namespace App\Models;

use App\Models\RequisitionItem;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Requisition extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date_filed' => 'date',
        'date_needed' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Requisition $requisition) {
            $requisition->requisition_status_id ??= RequisitionStatus::idOf('stock_pending');
            $requisition->reference_no ??= static::nextReferenceNo();
        });

        static::created(function (Requisition $requisition) {
            $requisition->histories()->create([
                'requisition_status_id' => $requisition->requisition_status_id,
                'user_id' => auth()->id(),
                'note' => 'Request filed',
            ]);
        });
    }

    public static function nextReferenceNo(): string
    {
        $prefix = 'REQ-' . now()->format('Ymd') . '-';

        $last = static::where('reference_no', 'like', $prefix . '%')->max('reference_no');
        $next = $last ? ((int) substr($last, -3)) + 1 : 1;

        return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(RequisitionStatus::class, 'requisition_status_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(RequisitionHistory::class);
    }

    public function latestHistory(): HasOne
    {
        return $this->hasOne(RequisitionHistory::class)->latestOfMany();
    }

    /** Change status and log it, in one transaction. */
    public function moveTo(string $code, ?string $note = null): void
    {
        $statusId = RequisitionStatus::idOf($code);

        DB::transaction(function () use ($statusId, $note) {
            $this->update(['requisition_status_id' => $statusId]);

            $this->histories()->create([
                'requisition_status_id' => $statusId,
                'user_id' => auth()->id(),
                'note' => $note,
            ]);
        });
    }

    public function isEditable(): bool
    {
        return match ($this->status->code) {
            'returned' => true,
            'stock_pending' => ! $this->items()
                ->whereHas('stockStatus', fn ($q) => $q->where('code', '!=', 'unchecked'))
                ->exists(),
            default => false,
        };
    }

    public function isCancellable(): bool
    {
        return in_array($this->status->code, ['stock_pending', 'returned']);
    }

    protected function total(): Attribute
    {
        return Attribute::get(fn () => $this->items->sum(
            fn (RequisitionItem $item) => $item->subtotal
        ));
    }
}
