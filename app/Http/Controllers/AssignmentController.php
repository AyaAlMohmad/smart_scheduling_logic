<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssignmentRequest;
use App\Http\Requests\UpdateAssignmentRequest;
use App\Models\AssignmentExam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $assignments = $request->user()->assignmentsExams()
            ->with('subject')
            ->orderBy('due_date')
            ->paginate(15);

        return view('assignments.index', compact('assignments'));
    }

    public function create(Request $request): View
    {
        return view('assignments.create', [
            'subjects' => $request->user()->subjects()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreAssignmentRequest $request): RedirectResponse
    {
        $request->user()->assignmentsExams()->create($request->validated());

        return redirect()->route('assignments.index')->with('success', 'Assignment/exam created.');
    }

    public function show(AssignmentExam $assignment): View
    {
        abort_unless($assignment->user_id === request()->user()->id, 403);

        return view('assignments.show', compact('assignment'));
    }

    public function edit(Request $request, AssignmentExam $assignment): View
    {
        abort_unless($assignment->user_id === $request->user()->id, 403);

        return view('assignments.edit', [
            'assignment' => $assignment,
            'subjects' => $request->user()->subjects()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateAssignmentRequest $request, AssignmentExam $assignment): RedirectResponse
    {
        $assignment->update($request->validated());

        return redirect()->route('assignments.index')->with('success', 'Updated.');
    }

    public function complete(Request $request, AssignmentExam $assignment): RedirectResponse
    {
        abort_unless($assignment->user_id === $request->user()->id, 403);

        $assignment->update(['status' => AssignmentExam::STATUS_COMPLETED]);

        return back()->with('success', 'Marked as completed.');
    }

    public function destroy(Request $request, AssignmentExam $assignment): RedirectResponse
    {
        abort_unless($assignment->user_id === $request->user()->id, 403);

        $assignment->delete();

        return redirect()->route('assignments.index')->with('success', 'Deleted.');
    }
}
