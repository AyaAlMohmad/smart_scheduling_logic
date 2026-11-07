<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'date', 'energy_level'])]
class EnergyLog extends Model
{
    use HasFactory;

    public const LEVEL_LOW = 'low';
    public const LEVEL_MEDIUM = 'medium';
    public const LEVEL_HIGH = 'high';

    public const LEVELS = [
        self::LEVEL_LOW,
        self::LEVEL_MEDIUM,
        self::LEVEL_HIGH,
    ];

    /** Numeric weight used by the smart scheduler. */
    public function energyWeight(): int
    {
        return match ($this->energy_level) {
            self::LEVEL_HIGH => 3,
            self::LEVEL_MEDIUM => 2,
            default => 1,
        };
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
