<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\AssignmentExam;
use App\Models\AvailabilitySlot;
use App\Models\EnergyLog;
use App\Models\StudySession;
use App\Models\Subject;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Smart scheduling engine (نظام الجدولة الذكي).
 *
 * Distributes study sessions across the week by combining:
 *  - the user's weekly availability windows (availability_slots)
 *  - subject difficulty weights (hard subjects get more + earlier slots)
 *  - the user's logged daily energy level (hard subjects land on high-energy days)
 *  - pending assignment/exam deadlines (urgent subjects are prioritised)
 */
class SmartScheduleService
{
    /** Default session length in minutes when splitting free slots. */
    private const SESSION_MINUTES = 60;

    /** Minimum usable free-slot length in minutes. */
    private const MIN_SLOT_MINUTES = 30;

    /**
     * Generate a smart weekly schedule for the user.
     *
     * Existing auto-generated sessions for the target week are replaced;
     * completed / in-progress sessions are preserved.
     *
     * @return Collection<int, StudySession> the newly created sessions
     */
    public function generateWeeklySchedule(User $user, ?CarbonImmutable $weekStart = null): Collection
    {
        $weekStart ??= CarbonImmutable::now()->startOfWeek();
        $weekEnd = $weekStart->endOfWeek();

        $subjects = $user->subjects()->get();
        if ($subjects->isEmpty()) {
            return collect();
        }

        $availability = $user->availabilitySlots()->get()->groupBy('day_of_week');
        $energyByDate = $this->energyMap($user, $weekStart, $weekEnd);
        $subjectWeights = $this->subjectWeights($user, $subjects, $weekEnd);

        return DB::transaction(function () use ($user, $subjects, $availability, $energyByDate, $subjectWeights, $weekStart, $weekEnd) {
            // Drop previously generated (still pending) sessions for that week.
            $user->studySessions()
                ->whereBetween('scheduled_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->where('status', StudySession::STATUS_SCHEDULED)
                ->delete();

            // Preserve occupied time (completed / in-progress sessions stay).
            $occupied = $user->studySessions()
                ->whereBetween('scheduled_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->get();

            $created = collect();

            // Order days by energy: hardest subjects first on the highest-energy days.
            $days = collect(range(0, 6))
                ->map(fn (int $offset) => $weekStart->addDays($offset))
                ->sortByDesc(fn (CarbonImmutable $d) => $energyByDate[$d->toDateString()] ?? 2)
                ->values();

            $subjectsByNeed = $subjects->sortByDesc(fn (Subject $s) => $subjectWeights[$s->id] ?? 0)->values();

            foreach ($days as $date) {
                $dateStr = $date->toDateString();
                $slots = $availability->get($date->dayOfWeek, collect());
                $dayEnergy = $energyByDate[$dateStr] ?? 2; // default medium

                // Prefer subjects whose difficulty matches today's energy.
                $orderedSubjects = $subjectsByNeed->sortByDesc(function (Subject $s) use ($subjectWeights, $dayEnergy) {
                    $match = $s->difficultyWeight() <= $dayEnergy ? 10 : 0;
                    return $match + ($subjectWeights[$s->id] ?? 0);
                });

                foreach ($slots as $slot) {
                    $freeIntervals = $this->subtractOccupied(
                        CarbonImmutable::parse($dateStr . ' ' . $slot->start_time),
                        CarbonImmutable::parse($dateStr . ' ' . $slot->end_time),
                        $occupied->filter(fn (StudySession $s) => $s->scheduled_date->toDateString() === $dateStr),
                        $created->filter(fn (StudySession $s) => $s->scheduled_date->toDateString() === $dateStr),
                    );

                    $i = 0;
                    foreach ($freeIntervals as [$start, $end]) {
                        while ($start->addMinutes(self::SESSION_MINUTES)->lte($end)) {
                            $subject = $orderedSubjects->get($i % max(1, $orderedSubjects->count()));
                            $i++;

                            $sessionEnd = $start->addMinutes(self::SESSION_MINUTES);
                            $created->push(StudySession::create([
                                'user_id' => $user->id,
                                'subject_id' => $subject->id,
                                'scheduled_date' => $dateStr,
                                'start_time' => $start->format('H:i:s'),
                                'end_time' => $sessionEnd->format('H:i:s'),
                                'status' => StudySession::STATUS_SCHEDULED,
                            ]));
                            $start = $sessionEnd;
                        }
                    }
                }
            }

            return $created;
        });
    }

    /**
     * Reschedule a single session to the next free availability slot.
     * Returns the moved session or null when no slot is available.
     */
    public function rescheduleSession(StudySession $session): ?StudySession
    {
        $user = $session->user;
        $duration = CarbonImmutable::parse($session->start_time)
            ->diffInMinutes(CarbonImmutable::parse($session->end_time));

        $availability = $user->availabilitySlots()->get()->groupBy('day_of_week');
        $occupied = $user->studySessions()
            ->active()
            ->where('id', '!=', $session->id)
            ->where('scheduled_date', '>=', now()->toDateString())
            ->get();

        for ($offset = 0; $offset < 14; $offset++) {
            $date = CarbonImmutable::now()->addDays($offset);
            if ($offset === 0) {
                $date = CarbonImmutable::parse($session->scheduled_date)->lt($date)
                    ? CarbonImmutable::now()
                    : CarbonImmutable::parse($session->scheduled_date);
            }
            $dateStr = $date->toDateString();

            foreach ($availability->get($date->dayOfWeek, collect()) as $slot) {
                $free = $this->subtractOccupied(
                    CarbonImmutable::parse($dateStr . ' ' . $slot->start_time),
                    CarbonImmutable::parse($dateStr . ' ' . $slot->end_time),
                    $occupied->filter(fn (StudySession $s) => $s->scheduled_date->toDateString() === $dateStr),
                );

                foreach ($free as [$start, $end]) {
                    if ($date->isToday() && $start->isPast()) {
                        $start = CarbonImmutable::now()->ceilMinutes(15);
                    }
                    if ($start->addMinutes($duration)->lte($end)) {
                        $session->update([
                            'scheduled_date' => $dateStr,
                            'start_time' => $start->format('H:i:s'),
                            'end_time' => $start->addMinutes($duration)->format('H:i:s'),
                            'status' => StudySession::STATUS_RESCHEDULED,
                        ]);

                        AppNotification::create([
                            'user_id' => $user->id,
                            'title' => 'Session rescheduled',
                            'message' => "Your {$session->subject->name} session was moved to {$dateStr} at {$start->format('H:i')}.",
                        ]);

                        return $session->refresh();
                    }
                }
            }
        }

        return null;
    }

    /**
     * Detect sessions overlapping each other or falling outside availability,
     * and attempt to reschedule each conflicted session.
     *
     * @return array{rescheduled: int, failed: int}
     */
    public function resolveConflicts(User $user): array
    {
        $result = ['rescheduled' => 0, 'failed' => 0];

        $sessions = $user->studySessions()
            ->active()
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')->orderBy('start_time')
            ->get();

        $seen = [];
        foreach ($sessions as $session) {
            $key = $session->scheduled_date->toDateString();
            $start = CarbonImmutable::parse($session->start_time);
            $end = CarbonImmutable::parse($session->end_time);

            $conflict = ($seen[$key] ?? collect())->contains(
                fn (array $i) => $start->lt($i[1]) && $end->gt($i[0])
            );

            if ($conflict) {
                $this->rescheduleSession($session) ? $result['rescheduled']++ : $result['failed']++;
            } else {
                $seen[$key] = ($seen[$key] ?? collect())->push([$start, $end]);
            }
        }

        return $result;
    }

    /**
     * Map date => energy weight (1-3) for the week, from the user's energy logs.
     *
     * @return array<string, int>
     */
    private function energyMap(User $user, CarbonImmutable $start, CarbonImmutable $end): array
    {
        return $user->energyLogs()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->mapWithKeys(fn (EnergyLog $log) => [$log->date->toDateString() => $log->energyWeight()])
            ->all();
    }

    /**
     * Weight each subject by difficulty + urgency of its nearest pending deadline.
     *
     * @return array<int, float>
     */
    private function subjectWeights(User $user, Collection $subjects, CarbonImmutable $weekEnd): array
    {
        $nearestDue = AssignmentExam::pending()
            ->where('user_id', $user->id)
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->select('subject_id', DB::raw('MIN(due_date) as nearest'))
            ->groupBy('subject_id')
            ->pluck('nearest', 'subject_id');

        $weights = [];
        foreach ($subjects as $subject) {
            $weight = $subject->difficultyWeight() * 2;

            if (isset($nearestDue[$subject->id])) {
                $daysLeft = now()->diffInDays(CarbonImmutable::parse($nearestDue[$subject->id]), false);
                $weight += match (true) {
                    $daysLeft < 0 => 0,          // overdue handled elsewhere
                    $daysLeft <= 2 => 6,
                    $daysLeft <= 7 => 4,
                    default => 1,
                };
            }

            $weights[$subject->id] = $weight;
        }

        return $weights;
    }

    /**
     * Subtract occupied sessions from a free window, returning free intervals.
     *
     * @param Collection<int, StudySession> ...$occupiedGroups
     * @return array<int, array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    private function subtractOccupied(CarbonImmutable $start, CarbonImmutable $end, Collection ...$occupiedGroups): array
    {
        $intervals = [[$start, $end]];

        $occupied = collect($occupiedGroups)->flatten(1)
            ->sortBy(fn (StudySession $s) => $s->start_time);

        foreach ($occupied as $session) {
            $dateStr = $session->scheduled_date->toDateString();
            $busyStart = CarbonImmutable::parse($dateStr . ' ' . $session->start_time);
            $busyEnd = CarbonImmutable::parse($dateStr . ' ' . $session->end_time);

            $next = [];
            foreach ($intervals as [$s, $e]) {
                if ($busyEnd->lte($s) || $busyStart->gte($e)) {
                    $next[] = [$s, $e];
                    continue;
                }
                if ($busyStart->gt($s)) {
                    $next[] = [$s, $busyStart];
                }
                if ($busyEnd->lt($e)) {
                    $next[] = [$busyEnd, $e];
                }
            }
            $intervals = $next;
        }

        return array_values(array_filter(
            $intervals,
            fn (array $i) => $i[0]->diffInMinutes($i[1]) >= self::MIN_SLOT_MINUTES
        ));
    }
}
