<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePomodoroRequest;
use App\Models\PomodoroSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PomodoroController extends Controller
{
    public function index(Request $request): View
    {
        $sessions = $request->user()->pomodoroSessions()
            ->with('subject')
            ->latest()
            ->paginate(20);

        return view('pomodoro.index', [
            'sessions' => $sessions,
            'subjects' => $request->user()->subjects()->orderBy('name')->get(),
            'totalMinutesToday' => $request->user()->pomodoroSessions()
                ->whereDate('created_at', today())->sum('duration_minutes'),
        ]);
    }

    /**
     * Log a completed focus (pomodoro) session.
     */
    public function store(StorePomodoroRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $request->user()->pomodoroSessions()->create([
            'subject_id' => $data['subject_id'] ?? null,
            'duration_minutes' => $data['duration_minutes'],
            'started_at' => now()->subMinutes($data['duration_minutes']),
            'ended_at' => now(),
        ]);

        return back()->with('success', 'Focus session logged.');
    }

    public function destroy(Request $request, PomodoroSession $pomodoro): RedirectResponse
    {
        abort_unless($pomodoro->user_id === $request->user()->id, 403);

        $pomodoro->delete();

        return back()->with('success', 'Session removed.');
    }
}
