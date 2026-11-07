<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Subjects</h2></x-slot>
    <div class="py-12"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <a href="{{ route('subjects.create') }}" class="underline">New subject</a>
        <table class="mt-4 w-full bg-white shadow-sm sm:rounded-lg">
            <tr class="text-left"><th class="p-3">Name</th><th>Difficulty</th><th>Pending tasks</th><th></th></tr>
            @foreach($subjects as $subject)
                <tr class="border-t">
                    <td class="p-3"><a href="{{ route('subjects.show', $subject) }}" class="underline">{{ $subject->name }}</a></td>
                    <td>{{ $subject->difficulty_level }}</td>
                    <td>{{ $subject->pending_tasks }}</td>
                    <td>
                        <a href="{{ route('subjects.edit', $subject) }}" class="underline">Edit</a>
                        <form method="POST" action="{{ route('subjects.destroy', $subject) }}" class="inline">
                            @csrf @method('DELETE')
                            <button class="underline text-red-600">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </table>
        {{ $subjects->links() }}
    </div></div>
</x-app-layout>
