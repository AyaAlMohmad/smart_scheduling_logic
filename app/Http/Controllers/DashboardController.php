<?php

namespace App\Http\Controllers;

use App\Models\AssignmentExam;
use App\Models\EnergyLog;
use App\Models\StudySession;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Student overview: task stats, weekly schedule, energy tracker,
     * pomodoro totals and chart-ready analytics data.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $weekStart = CarbonImmutable::now()->startOfWeek();
        $weekEnd = $weekStart->endOfWeek();

        $taskCounts = $user->assignmentsExams()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $completed = (int) ($taskCounts[AssignmentExam::STATUS_COMPLETED] ?? 0);
        $pending = (int) ($taskCounts[AssignmentExam::STATUS_PENDING] ?? 0);
        $total = $completed + $pending;

        $dueSoon = $user->assignmentsExams()->with('subject')->dueSoon()->orderBy('due_date')->limit(5)->get();
        $overdue = $user->assignmentsExams()->pending()->where('due_date', '<', now())->count();

        $weeklySessions = $user->studySessions()
            ->with('subject')
            ->whereBetween('scheduled_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('scheduled_date')->orderBy('start_time')
            ->get()
            ->groupBy(fn (StudySession $s) => $s->scheduled_date->toDateString());

        $energyWeek = $user->energyLogs()
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get()
            ->keyBy(fn (EnergyLog $l) => $l->date->toDateString());

        $focusMinutesWeek = $user->pomodoroSessions()
            ->whereBetween('created_at', [$weekStart, $weekEnd->endOfDay()])
            ->sum('duration_minutes');

        // Chart data: tasks completed per day (last 14 days) + energy trend.
        $chartStart = CarbonImmutable::now()->subDays(13);
        $completedByDay = $user->assignmentsExams()
            ->completed()
            ->where('updated_at', '>=', $chartStart)
            ->select(DB::raw('DATE(updated_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')->pluck('total', 'day');

        $energyByDay = $user->energyLogs()
            ->where('date', '>=', $chartStart->toDateString())
            ->get()
            ->mapWithKeys(fn (EnergyLog $l) => [$l->date->toDateString() => $l->energyWeight()]);

        $chartLabels = collect(range(0, 13))->map(fn ($i) => $chartStart->addDays($i)->toDateString());

        $charts = [
            'labels' => $chartLabels,
            'completed_per_day' => $chartLabels->map(fn ($d) => (int) ($completedByDay[$d] ?? 0)),
            'energy_per_day' => $chartLabels->map(fn ($d) => $energyByDay[$d] ?? null),
            'subject_distribution' => $user->studySessions()
                ->whereBetween('scheduled_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->join('subjects', 'subjects.id', '=', 'study_sessions.subject_id')
                ->select('subjects.name', DB::raw('COUNT(*) as total'))
                ->groupBy('subjects.name')
                ->pluck('total', 'name'),
        ];

        return view('dashboard', [
            'completedTasks' => $completed,
            'pendingTasks' => $pending,
            'progressRate' => $total > 0 ? round($completed / $total * 100, 1) : 0,
            'dueSoon' => $dueSoon,
            'overdueCount' => $overdue,
            'weeklySessions' => $weeklySessions,
            'energyWeek' => $energyWeek,
            'focusMinutesWeek' => (int) $focusMinutesWeek,
            'unreadNotifications' => $user->appNotifications()->unread()->count(),
            'charts' => $charts,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
        ]);
    }
}
