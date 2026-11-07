<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'subject_id', 'title', 'description', 'type', 'due_date', 'status'])]
class AssignmentExam extends Model
{
    use HasFactory;

    protected $table = 'assignments_exams';

    public const TYPE_ASSIGNMENT = 'assignment';
    public const TYPE_EXAM = 'exam';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeDueSoon(Builder $query, int $days = 7): Builder
    {
        return $query->pending()->whereBetween('due_date', [now(), now()->addDays($days)]);
    }
}
