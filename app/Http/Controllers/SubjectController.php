<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $subjects = $request->user()->subjects()
            ->withCount(['assignmentsExams as pending_tasks' => fn ($q) => $q->pending()])
            ->latest()
            ->paginate(15);

        return view('subjects.index', compact('subjects'));
    }

    public function create(): View
    {
        return view('subjects.create');
    }

    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $request->user()->subjects()->create($request->validated());

        return redirect()->route('subjects.index')->with('success', 'Subject created.');
    }

    public function show(Subject $subject): View
    {
        abort_unless($subject->user_id === request()->user()->id, 403);

        $subject->load(['assignmentsExams' => fn ($q) => $q->orderBy('due_date')]);

        return view('subjects.show', compact('subject'));
    }

    public function edit(Subject $subject): View
    {
        abort_unless($subject->user_id === request()->user()->id, 403);

        return view('subjects.edit', compact('subject'));
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $subject->update($request->validated());

        return redirect()->route('subjects.index')->with('success', 'Subject updated.');
    }

    public function destroy(Request $request, Subject $subject): RedirectResponse
    {
        abort_unless($subject->user_id === $request->user()->id, 403);

        $subject->delete();

        return redirect()->route('subjects.index')->with('success', 'Subject deleted.');
    }
}
