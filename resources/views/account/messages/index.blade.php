<x-layouts.app title="Messenger — LocalDateHub">
    <div class="py-1 sm:py-4 max-w-[1720px] mx-auto px-1 sm:px-4 lg:px-6 space-y-1 sm:space-y-3">
        <!-- Breadcrumbs Navigation (Compact on desktop, hidden on tiny mobile to maximize messaging viewport) -->
        <div class="px-2 hidden sm:block">
            <x-breadcrumbs :items="[
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'My Account', 'url' => route('account.profile')],
                ['label' => 'Messenger']
            ]" />
        </div>

        <!-- Ultimate Messenger-Style Three-Column Interface -->
        <livewire:messenger.messenger-hub 
            :conversation="$conversation ?? null" 
            :listing="$listing ?? null" 
            :user="$user ?? null" 
        />
    </div>
</x-layouts.app>
