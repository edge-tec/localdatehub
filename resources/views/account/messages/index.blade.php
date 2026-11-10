<x-layouts.app title="Messenger — LocalDateHub">
    <div class="py-4 sm:py-6 max-w-[1720px] mx-auto px-2 sm:px-4 lg:px-6 space-y-3">
        <!-- Breadcrumbs Navigation -->
        <div class="px-2">
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
