<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $subject->name }}</h2></x-slot>
    <div class="py-12"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white p-6 shadow-sm sm:rounded-lg">
            <p>Difficulty: {{ $subject->difficulty_level }} | Priority: {{ $subject->priority }}</p>
            <h3 class="font-semibold mt-4">Assignments &amp; exams</h3>
            <ul class="list-disc ms-6">
                @forelse($subject->assignmentsExams as $task)
                    <li>[{{ $task->type }}] {{ $task->title }} — {{ $task->due_date->format('Y-m-d') }} ({{ $task->status }})</li>
                @empty
                    <li class="text-gray-500">No tasks yet.</li>
                @endforelse
            </ul>
        </div>
    </div></div>
</x-app-layout>
