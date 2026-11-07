<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Subject</h2></x-slot>
    <div class="py-12"><div class="max-w-xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('subjects.update', $subject) }}" class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
            @csrf @method('PUT')
            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input id="name" name="name" class="w-full" :value="old('name', $subject->name)" required />
                <x-input-error :messages="$errors->get('name')" />
            </div>
            <div>
                <x-input-label for="difficulty_level" value="Difficulty" />
                <select id="difficulty_level" name="difficulty_level" class="w-full rounded-md border-gray-300">
                    @foreach(['low', 'medium', 'high'] as $level)
                        <option value="{{ $level }}" @selected(old('difficulty_level', $subject->difficulty_level) === $level)>{{ ucfirst($level) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="priority" value="Priority (1-5)" />
                <x-text-input id="priority" name="priority" type="number" min="1" max="5" :value="old('priority', $subject->priority)" />
            </div>
            <x-primary-button>Update</x-primary-button>
        </form>
    </div></div>
</x-app-layout>
