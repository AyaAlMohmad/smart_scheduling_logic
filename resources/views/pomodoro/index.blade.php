<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Pomodoro Focus</h2></x-slot>
    <div class="py-12"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white p-6 shadow-sm sm:rounded-lg">
            <p>Focus time today: <strong>{{ $totalMinutesToday }} min</strong></p>
            <form method="POST" action="{{ route('pomodoro.store') }}" class="mt-4 flex gap-2 items-end">
                @csrf
                <div>
                    <x-input-label for="subject_id" value="Subject" />
                    <select id="subject_id" name="subject_id" class="rounded-md border-gray-300">
                        <option value="">—</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="duration_minutes" value="Minutes" />
                    <x-text-input id="duration_minutes" name="duration_minutes" type="number" min="1" max="180" value="25" required />
                </div>
                <x-primary-button>Log session</x-primary-button>
            </form>
        </div>

        <div class="bg-white p-6 shadow-sm sm:rounded-lg">
            <ul class="list-disc ms-6">
                @foreach($sessions as $session)
                    <li>
                        {{ $session->created_at->format('Y-m-d H:i') }} — {{ $session->duration_minutes }} min
                        {{ $session->subject?->name }}
                        <form method="POST" action="{{ route('pomodoro.destroy', $session) }}" class="inline">@csrf @method('DELETE')
                            <button class="underline text-red-600">delete</button>
                        </form>
                    </li>
                @endforeach
            </ul>
            {{ $sessions->links() }}
        </div>
    </div></div>
</x-app-layout>
