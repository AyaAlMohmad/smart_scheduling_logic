<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Notifications</h2></x-slot>
    <div class="py-12"><div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('notifications.read-all') }}">@csrf
            <button class="underline">Mark all as read</button>
        </form>
        <div class="mt-4 space-y-2">
            @forelse($notifications as $notification)
                <div class="bg-white p-4 shadow-sm sm:rounded-lg {{ $notification->is_read ? 'opacity-60' : '' }}">
                    <strong>{{ $notification->title }}</strong>
                    <p>{{ $notification->message }}</p>
                    <div class="text-sm text-gray-500">{{ $notification->created_at->diffForHumans() }}</div>
                    @unless($notification->is_read)
                        <form method="POST" action="{{ route('notifications.read', $notification) }}" class="inline">@csrf @method('PATCH')
                            <button class="underline">mark read</button>
                        </form>
                    @endunless
                    <form method="POST" action="{{ route('notifications.destroy', $notification) }}" class="inline">@csrf @method('DELETE')
                        <button class="underline text-red-600">delete</button>
                    </form>
                </div>
            @empty
                <p class="text-gray-500">No notifications.</p>
            @endforelse
        </div>
        {{ $notifications->links() }}
    </div></div>
</x-app-layout>
