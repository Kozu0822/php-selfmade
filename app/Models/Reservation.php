<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reservation extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'symptom_id',
        'time_slot_id',
        'status',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function symptom(): BelongsTo
    {
        return $this->belongsTo(Symptom::class);
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function parts(): BelongsToMany
    {
        return $this->belongsToMany(Part::class)->withTimestamps();
    }

    public function statusLabel(): string
    {
        if ($this->status === 'pending') {
            return '予約中';
        }

        if ($this->status === 'no_show') {
            return '来店なし';
        }

        return 'キャンセル済み';
    }

    public function statusClass(): string
    {
        if ($this->status === 'pending') {
            return 'status-active';
        }

        if ($this->status === 'no_show') {
            return 'status-no-show';
        }

        return 'status-cancel';
    }

    public function canBeCancelledByAdmin(): bool
    {
        return in_array($this->status, ['pending', 'no_show']);
    }

    public function canBeCancelledByUser(): bool
    {
        return in_array($this->status, ['pending', 'no_show']);
    }
}
