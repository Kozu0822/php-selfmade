<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeSlot extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slot_at',
        'is_open',
        'is_reserved',
    ];

    protected function casts(): array
    {
        return [
            'slot_at' => 'datetime',
            'is_open' => 'boolean',
            'is_reserved' => 'boolean',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
