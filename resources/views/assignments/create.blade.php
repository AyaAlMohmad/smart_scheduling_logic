<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">New Assignment / Exam</h2></x-slot>
    <div class="py-12"><div class="max-w-xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('assignments.store') }}" class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
            @csrf
            <div>
                <x-input-label for="title" value="Title" />
                <x-text-input id="title" name="title" class="w-full" :value="old('title')" required />
                <x-input-error :messages="$errors->get('title')" />
            </div>
            <div>
                <x-input-label for="subject_id" value="Subject" />
                <select id="subject_id" name="subject_id" class="w-full rounded-md border-gray-300">
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected(old('subject_id') == $subject->id)>{{ $subject->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('subject_id')" />
            </div>
            <div>
                <x-input-label for="type" value="Type" />
                <select id="type" name="type" class="w-full rounded-md border-gray-300">
                    <option value="assignment">Assignment</option>
                    <option value="exam">Exam</option>
                </select>
            </div>
            <div>
                <x-input-label for="due_date" value="Due date" />
                <x-text-input id="due_date" name="due_date" type="datetime-local" class="w-full" :value="old('due_date')" required />
                <x-input-error :messages="$errors->get('due_date')" />
            </div>
            <div>
                <x-input-label for="description" value="Description" />
                <textarea id="description" name="description" class="w-full rounded-md border-gray-300">{{ old('description') }}</textarea>
            </div>
            <x-primary-button>Save</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
