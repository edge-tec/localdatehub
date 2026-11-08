<x-layouts.app title="Post Your Ad — LocalDateHub" description="Share your personal ad and connect with local people in your area on LocalDateHub.">
    <div class="py-8 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <!-- Hero Banner Matching Reference Screenshot -->
        <div class="relative rounded-3xl overflow-hidden shadow-2xl border border-[#2A2338] min-h-[220px] sm:min-h-[260px] flex items-center">
            <!-- Background Image of Model -->
            <img 
                src="{{ asset('images/wizard-hero.jpg') }}" 
                alt="Post Your Ad" 
                class="absolute inset-0 w-full h-full object-cover object-top"
            />
            
            <!-- Dark Gradient Overlay for High Contrast Text -->
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[#120E24]/70 to-[#120E24]/95"></div>

            <!-- Content on the right -->
            <div class="relative z-10 ml-auto p-6 sm:p-10 max-w-xl text-right flex flex-col items-end">
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white tracking-tight drop-shadow-md">
                    Post Your Ad
                </h1>
                <p class="text-sm sm:text-base text-slate-200 mt-2 font-medium max-w-md drop-shadow">
                    Share your personal ad and connect with local people in your area.
                </p>

                <!-- Neon Outlined Heart Icon -->
                <div class="mt-4 text-[#F20B83]">
                    <svg class="w-10 h-10 drop-shadow-[0_0_12px_rgba(242,11,131,0.8)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- 6-Step Classified Ad Posting Wizard Component -->
        @livewire('listing-wizard')

        <!-- Need Help? Support Banner Matching Screenshot -->
        <div class="relative rounded-3xl overflow-hidden shadow-xl border border-[#2A2338] p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <!-- Background Image -->
            <img 
                src="{{ asset('images/wizard-help-banner.jpg') }}" 
                alt="Support Team" 
                class="absolute inset-0 w-full h-full object-cover object-center"
            />
            <div class="absolute inset-0 bg-gradient-to-r from-[#171328]/95 via-[#171328]/80 to-transparent"></div>

            <!-- Left: Headset Icon + Text -->
            <div class="relative z-10 flex items-center space-x-4">
                <div class="w-14 h-14 rounded-2xl bg-[#F20B83]/20 border border-[#F20B83]/40 flex items-center justify-center text-[#F20B83] shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 1.5a9 9 0 00-9 9v1.5a3 3 0 003 3h.75a.75.75 0 00.75-.75V9.75a.75.75 0 00-.75-.75H4.5a7.5 7.5 0 0115 0h-1.5a.75.75 0 00-.75.75v4.5c0 .414.336.75.75.75h.75a3 3 0 003-3V10.5a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-black text-white">Need Help?</h3>
                    <p class="text-xs sm:text-sm text-slate-300">Our support team is here to help!</p>
                </div>
            </div>

            <!-- Right: Contact Us Button -->
            <div class="relative z-10 shrink-0">
                <a 
                    href="{{ Route::has('pages.contact') ? route('pages.contact') : url('/contact') }}" 
                    class="inline-flex items-center space-x-2 bg-[#F20B83] hover:bg-[#d90858] text-white px-6 py-2.5 rounded-full font-bold text-sm shadow-lg transition"
                >
                    <span>Contact Us</span>
                    <span>→</span>
                </a>
            </div>
        </div>

    </div>
</x-layouts.app>

