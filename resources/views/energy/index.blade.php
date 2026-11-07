<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Energy Tracker</h2></x-slot>
    <div class="py-12"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <form method="POST" action="{{ route('energy.store') }}" class="bg-white p-6 shadow-sm sm:rounded-lg flex gap-2 items-end">
            @csrf
            <div>
                <x-input-label for="date" value="Date" />
                <x-text-input id="date" name="date" type="date" :value="today()->toDateString()" required />
            </div>
            <div>
                <x-input-label for="energy_level" value="Energy" />
                <select id="energy_level" name="energy_level" class="rounded-md border-gray-300">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <x-primary-button>Save</x-primary-button>
        </form>

        <div class="bg-white p-6 shadow-sm sm:rounded-lg">
            <ul class="list-disc ms-6">
                @foreach($logs as $log)
                    <li>
                        {{ $log->date->toDateString() }} — {{ $log->energy_level }}
                        <form method="POST" action="{{ route('energy.destroy', $log) }}" class="inline">@csrf @method('DELETE')
                            <button class="underline text-red-600">delete</button>
                        </form>
                    </li>
                @endforeach
            </ul>
            {{ $logs->links() }}
        </div>
    </div></div>
</x-app-layout>
