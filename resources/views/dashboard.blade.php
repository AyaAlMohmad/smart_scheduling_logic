<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    {{--
        Available data for the frontend developer:
        $completedTasks, $pendingTasks, $progressRate, $overdueCount,
        $dueSoon (Collection<AssignmentExam>), $weeklySessions (grouped by date),
        $energyWeek (keyed by date), $focusMinutesWeek, $unreadNotifications,
        $charts (labels, completed_per_day, energy_per_day, subject_distribution),
        $weekStart, $weekEnd
    --}}

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="text-sm text-gray-500">Progress</div>
                    <div class="text-3xl font-bold">{{ $progressRate }}%</div>
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="text-sm text-gray-500">Pending tasks</div>
                    <div class="text-3xl font-bold">{{ $pendingTasks }}</div>
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="text-sm text-gray-500">Completed</div>
                    <div class="text-3xl font-bold">{{ $completedTasks }}</div>
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="text-sm text-gray-500">Focus minutes (week)</div>
                    <div class="text-3xl font-bold">{{ $focusMinutesWeek }}</div>
                </div>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="font-semibold mb-2">This week's sessions</h3>
                @forelse($weeklySessions as $date => $sessions)
                    <div class="mb-2">
                        <strong>{{ $date }}</strong>
                        <ul class="list-disc ms-6">
                            @foreach($sessions as $session)
                                <li>{{ $session->subject->name }} — {{ substr($session->start_time, 0, 5) }}–{{ substr($session->end_time, 0, 5) }} ({{ $session->status }})</li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <p class="text-gray-500">No sessions scheduled this week.</p>
                @endforelse
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="font-semibold mb-2">Due soon</h3>
                <ul class="list-disc ms-6">
                    @forelse($dueSoon as $task)
                        <li>[{{ $task->type }}] {{ $task->title }} — {{ $task->subject->name }}, due {{ $task->due_date->format('Y-m-d') }}</li>
                    @empty
                        <li class="text-gray-500">Nothing due in the next 7 days.</li>
                    @endforelse
                </ul>
            </div>

            {{-- Chart-ready data exposed to JS --}}
            <script id="dashboard-charts" type="application/json">
                {!! json_encode($charts) !!}
            </script>
        </div>
    </div>
</x-app-layout>
