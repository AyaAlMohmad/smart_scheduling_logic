<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Task</h2></x-slot>
    <div class="py-12"><div class="max-w-xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('assignments.update', $assignment) }}" class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
            @csrf @method('PUT')
            <div>
                <x-input-label for="title" value="Title" />
                <x-text-input id="title" name="title" class="w-full" :value="old('title', $assignment->title)" required />
            </div>
            <div>
                <x-input-label for="subject_id" value="Subject" />
                <select id="subject_id" name="subject_id" class="w-full rounded-md border-gray-300">
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected(old('subject_id', $assignment->subject_id) == $subject->id)>{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="type" value="Type" />
                <select id="type" name="type" class="w-full rounded-md border-gray-300">
                    <option value="assignment" @selected($assignment->type === 'assignment')>Assignment</option>
                    <option value="exam" @selected($assignment->type === 'exam')>Exam</option>
                </select>
            </div>
            <div>
                <x-input-label for="due_date" value="Due date" />
                <x-text-input id="due_date" name="due_date" type="datetime-local" class="w-full" :value="old('due_date', $assignment->due_date->format('Y-m-d\TH:i'))" required />
            </div>
            <div>
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="w-full rounded-md border-gray-300">
                    <option value="pending" @selected($assignment->status === 'pending')>Pending</option>
                    <option value="completed" @selected($assignment->status === 'completed')>Completed</option>
                </select>
            </div>
            <x-primary-button>Update</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
