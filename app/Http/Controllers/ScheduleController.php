<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateScheduleRequest;
use App\Http\Requests\StoreAvailabilitySlotRequest;
use App\Http\Requests\StoreStudySessionRequest;
use App\Http\Requests\UpdateStudySessionRequest;
use App\Models\StudySession;
use App\Services\SmartScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(private readonly SmartScheduleService $scheduler)
    {
    }

    /**
     * Show the weekly study schedule.
     */
    public function index(Request $request): View
    {
        $weekStart = CarbonImmutable::parse($request->query('week', 'now'))->startOfWeek();

        $sessions = $request->user()->studySessions()
            ->with('subject')
            ->whereBetween('scheduled_date', [$weekStart->toDateString(), $weekStart->endOfWeek()->toDateString()])
            ->orderBy('scheduled_date')->orderBy('start_time')
            ->get()
            ->groupBy(fn (StudySession $s) => $s->scheduled_date->toDateString());

        return view('schedule.index', [
            'sessions' => $sessions,
            'weekStart' => $weekStart,
            'weekEnd' => $weekStart->endOfWeek(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('schedule.create', [
            'subjects' => $request->user()->subjects()->orderBy('name')->get(),
        ]);
    }

    /**
     * Manually create a single study session.
     */
    public function store(StoreStudySessionRequest $request): RedirectResponse
    {
        $request->user()->studySessions()->create([
            ...$request->validated(),
            'status' => StudySession::STATUS_SCHEDULED,
        ]);

        return redirect()->route('schedule.index')->with('success', 'Session scheduled.');
    }

    public function edit(Request $request, StudySession $session): View
    {
        abort_unless($session->user_id === $request->user()->id, 403);

        return view('schedule.edit', [
            'session' => $session,
            'subjects' => $request->user()->subjects()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateStudySessionRequest $request, StudySession $session): RedirectResponse
    {
        $session->update($request->validated());

        return redirect()->route('schedule.index')->with('success', 'Session updated.');
    }

    public function destroy(Request $request, StudySession $session): RedirectResponse
    {
        abort_unless($session->user_id === $request->user()->id, 403);

        $session->delete();

        return redirect()->route('schedule.index')->with('success', 'Session removed.');
    }

    /**
     * Run the smart scheduler to distribute sessions across the week.
     */
    public function generate(GenerateScheduleRequest $request): RedirectResponse
    {
        $weekStart = $request->filled('week_start')
            ? CarbonImmutable::parse($request->input('week_start'))->startOfWeek()
            : null;

        $created = $this->scheduler->generateWeeklySchedule($request->user(), $weekStart);

        return redirect()->route('schedule.index')
            ->with('success', "Smart schedule generated: {$created->count()} sessions created.");
    }

    /**
     * Auto-reschedule a single session to the next free slot.
     */
    public function reschedule(Request $request, StudySession $session): RedirectResponse
    {
        abort_unless($session->user_id === $request->user()->id, 403);

        $moved = $this->scheduler->rescheduleSession($session);

        return back()->with(
            $moved ? 'success' : 'error',
            $moved ? 'Session rescheduled.' : 'No free slot found in the next two weeks.'
        );
    }

    /**
     * Detect and auto-fix conflicting sessions.
     */
    public function resolveConflicts(Request $request): RedirectResponse
    {
        $result = $this->scheduler->resolveConflicts($request->user());

        return back()->with('success', "Resolved {$result['rescheduled']} conflicts" .
            ($result['failed'] ? " ({$result['failed']} could not be placed)" : '') . '.');
    }

    /**
     * Replace the user's weekly availability windows.
     */
    public function storeAvailability(StoreAvailabilitySlotRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->availabilitySlots()->delete();
        foreach ($request->validated('slots') as $slot) {
            $user->availabilitySlots()->create($slot);
        }

        return back()->with('success', 'Availability updated.');
    }
}
