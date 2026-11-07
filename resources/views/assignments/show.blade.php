<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $assignment->title }}</h2></x-slot>
    <div class="py-12"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white p-6 shadow-sm sm:rounded-lg">
            <p>Subject: {{ $assignment->subject->name }}</p>
            <p>Type: {{ $assignment->type }} | Status: {{ $assignment->status }}</p>
            <p>Due: {{ $assignment->due_date->format('Y-m-d H:i') }}</p>
            @if($assignment->description)<p class="mt-2">{{ $assignment->description }}</p>@endif
        </div>
    </div></div>
</x-app-layout>
