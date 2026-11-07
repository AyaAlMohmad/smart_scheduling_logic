<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Study Schedule — week of {{ $weekStart->toDateString() }}</h2></x-slot>
    <div class="py-12"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="flex gap-4">
            <a href="{{ route('schedule.create') }}" class="underline">Add session</a>
            <form method="POST" action="{{ route('schedule.generate') }}">@csrf
                <button class="underline text-indigo-600">Generate smart schedule</button>
            </form>
            <form method="POST" action="{{ route('schedule.resolve-conflicts') }}">@csrf
                <button class="underline text-orange-600">Resolve conflicts</button>
            </form>
        </div>

        <div class="bg-white p-6 shadow-sm sm:rounded-lg">
            @forelse($sessions as $date => $daySessions)
                <div class="mb-4">
                    <h3 class="font-semibold">{{ $date }}</h3>
                    <ul class="list-disc ms-6">
                        @foreach($daySessions as $session)
                            <li>
                                {{ substr($session->start_time, 0, 5) }}–{{ substr($session->end_time, 0, 5) }}
                                {{ $session->subject->name }} ({{ $session->status }})
                                <a href="{{ route('schedule.edit', $session) }}" class="underline">edit</a>
                                <form method="POST" action="{{ route('schedule.reschedule', $session) }}" class="inline">@csrf
                                    <button class="underline">reschedule</button>
                                </form>
                                <form method="POST" action="{{ route('schedule.destroy', $session) }}" class="inline">@csrf @method('DELETE')
                                    <button class="underline text-red-600">delete</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <p class="text-gray-500">No sessions this week. Set your availability, then generate a smart schedule.</p>
            @endforelse
        </div>

        <div class="bg-white p-6 shadow-sm sm:rounded-lg">
            <h3 class="font-semibold mb-2">Weekly availability</h3>
            <form method="POST" action="{{ route('schedule.availability') }}">
                @csrf
                <p class="text-sm text-gray-500 mb-2">Add rows like: <code>slots[0][day_of_week]=1&slots[0][start_time]=16:00&slots[0][end_time]=19:00</code> — the frontend will replace this stub.</p>
                @for($i = 0; $i < 3; $i++)
                    <div class="flex gap-2 mb-2">
                        <select name="slots[{{ $i }}][day_of_week]" class="rounded-md border-gray-300">
                            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d => $label)
                                <option value="{{ $d }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="time" name="slots[{{ $i }}][start_time]" class="rounded-md border-gray-300" />
                        <input type="time" name="slots[{{ $i }}][end_time]" class="rounded-md border-gray-300" />
                    </div>
                @endfor
                <x-primary-button>Save availability</x-primary-button>
            </form>
        </div>
    </div></div>
</x-app-layout>
