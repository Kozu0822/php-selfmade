<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeSlot extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'version' => 1,
    ];

    protected $fillable = [
        'slot_at',
        'is_open',
        'is_reserved',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'slot_at' => 'datetime',
            'is_open' => 'boolean',
            'is_reserved' => 'boolean',
            'version' => 'integer',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
