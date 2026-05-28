<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Part extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'device_id',
        'name',
        'stock',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function symptoms(): BelongsToMany
    {
        return $this->belongsToMany(Symptom::class)->withTimestamps();
    }

    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class)->withTimestamps();
    }

    public function canBeDeleted(): bool
    {
        return !$this->reservations()
            ->whereIn('status', ['pending', 'no_show'])
            ->exists();
    }
}
