@props(['notifications', 'unreadNotificationsCount'])

@php
    use Filament\Support\Enums\Alignment;
    //$notifications = $notifications->filter(fn($item) => is_null($item->read_at));
    $hasNotifications = $notifications->count();
    $isPaginated = $notifications instanceof \Illuminate\Contracts\Pagination\Paginator && $notifications->hasPages();
@endphp

<x-filament::modal :alignment="$hasNotifications ? null : Alignment::Center" close-button :description="$hasNotifications ? null : __('filament-notifications::database.modal.empty.description')" :heading="$hasNotifications ? null : __('filament-notifications::database.modal.empty.heading')" :icon="$hasNotifications ? null : 'heroicon-o-bell-slash'"
    :icon-alias="$hasNotifications ? null : 'notifications::database.modal.empty-state'" :icon-color="$hasNotifications ? null : 'gray'" id="database-notifications" slide-over :sticky-header="$hasNotifications" width="md">
    @if ($hasNotifications)
        <x-slot name="header">
            <div>
                <x-filament-notifications::database.modal.heading :unread-notifications-count="$unreadNotificationsCount" />

                {{-- <x-filament-notifications::database.modal.actions :notifications="$notifications" :unread-notifications-count="$unreadNotificationsCount" /> --}}
            </div>
        </x-slot>

        <div @class([
            '-mx-6 -mt-6 divide-y divide-gray-200 dark:divide-white/10',
            '-mb-6' => !$isPaginated,
            'border-b border-gray-200 dark:border-white/10' => $isPaginated,
        ])>
            @foreach ($notifications as $notification)
                <div @style([
                    '--c-50:var(--primary-50)' => $notification->unread(),
                ])>
                    {{ $this->getNotification($notification)->inline() }}
                </div>
            @endforeach
        </div>

        @if ($isPaginated)
            <x-slot name="footer">
                <x-filament::pagination :paginator="$notifications" />
            </x-slot>
        @endif
    @endif
</x-filament::modal>
