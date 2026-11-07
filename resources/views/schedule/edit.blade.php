<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Study Session</h2></x-slot>
    <div class="py-12"><div class="max-w-xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('schedule.update', $session) }}" class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
            @csrf @method('PUT')
            <div>
                <x-input-label for="subject_id" value="Subject" />
                <select id="subject_id" name="subject_id" class="w-full rounded-md border-gray-300">
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected($session->subject_id == $subject->id)>{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="scheduled_date" value="Date" />
                <x-text-input id="scheduled_date" name="scheduled_date" type="date" class="w-full" :value="old('scheduled_date', $session->scheduled_date->toDateString())" required />
            </div>
            <div class="flex gap-4">
                <div class="flex-1">
                    <x-input-label for="start_time" value="Start" />
                    <x-text-input id="start_time" name="start_time" type="time" class="w-full" :value="old('start_time', substr($session->start_time, 0, 5))" required />
                </div>
                <div class="flex-1">
                    <x-input-label for="end_time" value="End" />
                    <x-text-input id="end_time" name="end_time" type="time" class="w-full" :value="old('end_time', substr($session->end_time, 0, 5))" required />
                </div>
            </div>
            <div>
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="w-full rounded-md border-gray-300">
                    @foreach(['scheduled','in_progress','completed','missed','rescheduled'] as $s)
                        <option value="{{ $s }}" @selected($session->status === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <x-primary-button>Update</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
