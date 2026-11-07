<x-layouts.app title="Trust & Safety Standards — LocalDateHub">
    <div class="py-12 bg-slate-950">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-breadcrumbs :items="[
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Trust & Safety']
            ]" />

            <div class="mt-8">
                <x-badge variant="rose">Community Safety Standards</x-badge>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-white mt-3 tracking-tight">
                    Trust, Safety & Legal Compliance
                </h1>
                <p class="mt-4 text-lg text-slate-300 leading-relaxed">
                    LocalDateHub is committed to maintaining a lawful, private, and secure platform. Every tool, moderation filter, and architectural component is built with user protection as our highest priority.
                </p>
            </div>

            <!-- Emergency Banner -->
            <div class="mt-8 p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 flex items-start gap-3">
                <svg class="w-6 h-6 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="text-sm">
                    <strong class="font-semibold text-white">Emergency Resources:</strong> If you are in immediate danger, dial <strong>911</strong> immediately. For human trafficking support, contact the National Human Trafficking Hotline at <strong>1-888-373-7888</strong> (SMS: HELP to 233733).
                </div>
            </div>

            <!-- Main Sections -->
            <div class="mt-10 space-y-8">
                <!-- Section 1: 18+ Mandatory Age Assurance -->
                <x-card>
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-rose-500/20 text-rose-400 text-xs font-bold flex items-center justify-center">18+</span>
                        Mandatory 18+ Age Policy
                    </h2>
                    <p class="mt-3 text-sm text-slate-300 leading-relaxed">
                        LocalDateHub is strictly for consenting adults aged 18 and older. We enforce zero tolerance for underage access or representation. Any profile, listing, or message attempting to depict, reference, or contact individuals under 18 results in immediate permanent termination, IP banning, and automatic reporting to the National Center for Missing & Exploited Children (NCMEC).
                    </p>
                </x-card>

                <!-- Section 2: Prohibited Content & FOSTA-SESTA Compliance -->
                <x-card>
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        FOSTA-SESTA & Prohibited Content
                    </h2>
                    <p class="mt-3 text-sm text-slate-300 leading-relaxed">
                        In full compliance with federal laws, including 18 U.S.C. §§ 1591 and 2421A (FOSTA-SESTA), the following activities are strictly prohibited on LocalDateHub:
                    </p>
                    <ul class="mt-3 space-y-2 text-sm text-slate-300 list-disc list-inside">
                        <li>Commercial sex work, prostitution, or escort solicitation.</li>
                        <li>Any exchange of money, gifts, housing, or goods for intimate acts.</li>
                        <li>Non-consensual imagery or revenge pornography.</li>
                        <li>Promotion of illicit drugs, firearms, or violence.</li>
                        <li>Impersonation, catfishing, or blackmail attempts.</li>
                    </ul>
                </x-card>

                <!-- Section 3: Safe Dating Best Practices -->
                <x-card>
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Safe Dating & Meeting Guidelines
                    </h2>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-slate-300">
                        <div class="p-3 rounded-lg bg-slate-900 border border-slate-800">
                            <strong class="text-white block text-sm mb-1">Meet in Public First</strong>
                            Always schedule initial meetups in busy, well-lit public venues such as coffee shops, restaurants, or lounges.
                        </div>
                        <div class="p-3 rounded-lg bg-slate-900 border border-slate-800">
                            <strong class="text-white block text-sm mb-1">Inform a Trusted Friend</strong>
                            Share your plans, who you are meeting, where you will be, and check in with someone you trust before and after.
                        </div>
                        <div class="p-3 rounded-lg bg-slate-900 border border-slate-800">
                            <strong class="text-white block text-sm mb-1">Control Your Transportation</strong>
                            Drive yourself or take rideshare. Never let someone you haven’t met pick you up from your residence.
                        </div>
                        <div class="p-3 rounded-lg bg-slate-900 border border-slate-800">
                            <strong class="text-white block text-sm mb-1">Never Send Money</strong>
                            Anyone asking for money, gift cards, crypto, or financial wire transfers is a scammer. Report them immediately.
                        </div>
                    </div>
                </x-card>

                <!-- Section 4: Reporting Violations -->
                <x-card>
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        How to Report Violations
                    </h2>
                    <p class="mt-3 text-sm text-slate-300 leading-relaxed">
                        If you encounter a listing or user that violates our policies, use the <strong>"Report Listing"</strong> button located on every listing page, or reach out directly to our Trust & Safety team via our <a href="{{ route('pages.contact') }}" class="text-rose-400 underline hover:text-rose-300">Contact & Support Center</a>. All reports are confidential and reviewed within 1 hour.
                    </p>
                </x-card>
            </div>
        </div>
    </div>
</x-layouts.app>
