<x-layouts.app title="Pricing & Listing Upgrades — LocalDateHub">
    <div class="py-12 bg-slate-950">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-breadcrumbs :items="[
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Pricing & Upgrades']
            ]" />

            <div class="text-center max-w-3xl mx-auto mt-8">
                <x-badge variant="rose">Transparent Pricing</x-badge>
                <h1 class="text-3xl sm:text-5xl font-extrabold text-white mt-3 tracking-tight">
                    Simple, Transparent Listing Options
                </h1>
                <p class="mt-4 text-lg text-slate-300 leading-relaxed">
                    Always 100% free to post standard personal ads. Optional feature upgrades give your listing maximum visibility in your city and metro area.
                </p>
            </div>

            <!-- Pricing Grid -->
            <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
                @forelse($plans as $plan)
                    <div class="relative rounded-2xl bg-slate-900 border @if($plan->price_cents > 0) border-rose-500/50 shadow-xl shadow-rose-950/20 @else border-slate-800 @endif p-8 flex flex-col justify-between">
                        @if($plan->slug === 'city-featured')
                            <div class="absolute -top-3.5 left-1/2 transform -translate-x-1/2">
                                <span class="bg-gradient-to-r from-rose-600 to-pink-600 text-white text-[11px] font-bold uppercase tracking-wider py-1 px-3 rounded-full shadow-md">
                                    Most Popular
                                </span>
                            </div>
                        @endif

                        <div>
                            <h3 class="text-xl font-bold text-white">{{ $plan->name }}</h3>
                            <p class="mt-2 text-xs text-slate-400 min-h-[36px]">{{ $plan->description }}</p>

                            <div class="mt-6 flex items-baseline gap-1">
                                <span class="text-4xl font-extrabold text-white">
                                    ${{ number_format($plan->price_cents / 100, 2) }}
                                </span>
                                <span class="text-xs text-slate-400 font-medium">
                                    / {{ $plan->duration_days }} days
                                </span>
                            </div>

                            <ul class="mt-6 space-y-3 text-xs text-slate-300">
                                @if(is_array($plan->features))
                                    @foreach($plan->features as $feature)
                                        <li class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-800">
                            <a href="{{ route('listings.create') }}" class="block w-full py-2.5 text-center text-sm font-semibold rounded-xl transition duration-150 @if($plan->price_cents > 0) bg-rose-600 hover:bg-rose-500 text-white shadow-lg shadow-rose-600/20 @else bg-slate-800 hover:bg-slate-700 text-white @endif">
                                @if($plan->price_cents === 0)
                                    Post Free Personal Ad
                                @else
                                    Select {{ $plan->name }}
                                @endif
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-12">
                        <x-empty-state
                            title="No Pricing Plans Available"
                            description="Standard personal ads remain free to publish across all categories."
                            actionText="Post Free Ad"
                            :actionUrl="route('listings.create')"
                        />
                    </div>
                @endforelse
            </div>

            <!-- Upgrade FAQ -->
            <div class="mt-16 max-w-3xl mx-auto">
                <h2 class="text-2xl font-bold text-white text-center mb-8">Frequently Asked Questions</h2>
                <div class="space-y-4">
                    <x-card>
                        <h4 class="text-base font-semibold text-white">Are standard personal ads really free?</h4>
                        <p class="mt-1.5 text-xs text-slate-300 leading-relaxed">
                            Yes! Every registered user can publish standard personal classified ads with up to 4 photos at zero cost. There are no recurring surprise subscriptions.
                        </p>
                    </x-card>

                    <x-card>
                        <h4 class="text-base font-semibold text-white">How does City Featured work?</h4>
                        <p class="mt-1.5 text-xs text-slate-300 leading-relaxed">
                            A City Featured listing is pinned at the top of your specific city category directory with a distinctive highlighted badge and border, generating up to 3x more views.
                        </p>
                    </x-card>

                    <x-card>
                        <h4 class="text-base font-semibold text-white">Is my billing information private?</h4>
                        <p class="mt-1.5 text-xs text-slate-300 leading-relaxed">
                            All transactions are securely handled through PCI-DSS Level 1 compliant payment gateways. We never store raw credit card numbers on our servers.
                        </p>
                    </x-card>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

