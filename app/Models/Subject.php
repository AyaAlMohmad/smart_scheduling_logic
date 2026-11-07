<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'difficulty_level', 'color', 'priority'])]
class Subject extends Model
{
    use HasFactory;

    public const DIFFICULTY_LOW = 'low';
    public const DIFFICULTY_MEDIUM = 'medium';
    public const DIFFICULTY_HIGH = 'high';

    public const DIFFICULTY_LEVELS = [
        self::DIFFICULTY_LOW,
        self::DIFFICULTY_MEDIUM,
        self::DIFFICULTY_HIGH,
    ];

    /** Difficulty weight used by the smart scheduler. */
    public function difficultyWeight(): int
    {
        return match ($this->difficulty_level) {
            self::DIFFICULTY_HIGH => 3,
            self::DIFFICULTY_MEDIUM => 2,
            default => 1,
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignmentsExams(): HasMany
    {
        return $this->hasMany(AssignmentExam::class);
    }

    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }

    public function pomodoroSessions(): HasMany
    {
        return $this->hasMany(PomodoroSession::class);
    }
}
