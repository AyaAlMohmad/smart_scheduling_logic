<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Assignments &amp; Exams</h2></x-slot>
    <div class="py-12"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <a href="{{ route('assignments.create') }}" class="underline">New task</a>
        <table class="mt-4 w-full bg-white shadow-sm sm:rounded-lg">
            <tr class="text-left"><th class="p-3">Title</th><th>Subject</th><th>Type</th><th>Due</th><th>Status</th><th></th></tr>
            @foreach($assignments as $assignment)
                <tr class="border-t">
                    <td class="p-3">{{ $assignment->title }}</td>
                    <td>{{ $assignment->subject->name }}</td>
                    <td>{{ $assignment->type }}</td>
                    <td>{{ $assignment->due_date->format('Y-m-d H:i') }}</td>
                    <td>{{ $assignment->status }}</td>
                    <td>
                        @if($assignment->status === 'pending')
                            <form method="POST" action="{{ route('assignments.complete', $assignment) }}" class="inline">
                                @csrf @method('PATCH')
                                <button class="underline text-green-600">Complete</button>
                            </form>
                        @endif
                        <a href="{{ route('assignments.edit', $assignment) }}" class="underline">Edit</a>
                        <form method="POST" action="{{ route('assignments.destroy', $assignment) }}" class="inline">
                            @csrf @method('DELETE')
                            <button class="underline text-red-600">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </table>
        {{ $assignments->links() }}
    </div></div>
</x-app-layout>
