<x-layouts.app title="Contact & Support — LocalDateHub">
    <div class="py-12 bg-slate-950">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-breadcrumbs :items="[
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Contact Support']
            ]" />

            <div class="mt-8">
                <x-badge variant="rose">Help Center & Inquiries</x-badge>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-white mt-3 tracking-tight">
                    Get in Touch with LocalDateHub
                </h1>
                <p class="mt-4 text-lg text-slate-300 leading-relaxed">
                    Have questions about your account, need assistance with a listing, or wish to report a safety concern? Our USA-based operations team is here to assist.
                </p>
            </div>

            @if(session('success'))
                <div class="mt-6">
                    <x-alert type="success" :dismissible="false">
                        <strong class="font-semibold">Message Delivered:</strong> {{ session('success') }}
                    </x-alert>
                </div>
            @endif

            <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Info Column -->
                <div class="space-y-6">
                    <x-card>
                        <h2 class="text-base font-bold text-white mb-2">Response Times</h2>
                        <ul class="space-y-3 text-xs text-slate-300">
                            <li class="flex items-start gap-2">
                                <span class="w-2 h-2 rounded-full bg-rose-500 mt-1.5 shrink-0"></span>
                                <div>
                                    <strong class="text-white block">Trust & Safety Reports:</strong>
                                    Reviewed within 1 hour, 24/7.
                                </div>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-500 mt-1.5 shrink-0"></span>
                                <div>
                                    <strong class="text-white block">Billing & Upgrades:</strong>
                                    Responded within 4 to 8 hours.
                                </div>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 mt-1.5 shrink-0"></span>
                                <div>
                                    <strong class="text-white block">General Inquiries:</strong>
                                    Responded within 24 hours.
                                </div>
                            </li>
                        </ul>
                    </x-card>

                    <x-card>
                        <h2 class="text-base font-bold text-white mb-2">Encrypted Records</h2>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            All messages and contact information submitted through this form are stored with AES-256 field encryption and purged in compliance with our data retention schedule.
                        </p>
                    </x-card>
                </div>

                <!-- Form Column -->
                <div class="md:col-span-2">
                    <x-card>
                        <form action="{{ route('pages.contact.submit') }}" method="POST" class="space-y-5">
                            @csrf

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <x-input
                                    name="name"
                                    label="Your Full Name"
                                    placeholder="Jane Doe"
                                    :value="old('name', Auth::user()?->name)"
                                    required
                                />

                                <x-input
                                    name="email"
                                    type="email"
                                    label="Email Address"
                                    placeholder="jane@example.com"
                                    :value="old('email', Auth::user()?->email)"
                                    required
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <x-select
                                    name="inquiry_type"
                                    label="Inquiry Topic"
                                    :options="[
                                        'general_support' => 'General Account & Listing Support',
                                        'safety_report' => 'Trust & Safety Violation Report',
                                        'billing' => 'Billing & Featured Ad Upgrades',
                                        'partnership' => 'Media & Partnership Inquiries'
                                    ]"
                                    :selected="old('inquiry_type', 'general_support')"
                                    required
                                />

                                <x-input
                                    name="phone"
                                    type="tel"
                                    label="Phone Number (Optional)"
                                    placeholder="+1 (555) 000-0000"
                                    :value="old('phone')"
                                />
                            </div>

                            <x-input
                                name="subject"
                                label="Subject"
                                placeholder="Brief summary of your inquiry"
                                :value="old('subject')"
                                required
                            />

                            <div>
                                <label for="message" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
                                    Message Details <span class="text-rose-400">*</span>
                                </label>
                                <textarea
                                    id="message"
                                    name="message"
                                    rows="5"
                                    class="w-full bg-slate-900 border @error('message') border-rose-500 @else border-slate-700 @enderror rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition duration-150"
                                    placeholder="Please provide complete details so we can best assist you..."
                                    required
                                >{{ old('message') }}</textarea>
                                @error('message')
                                    <p class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="pt-2">
                                <x-button type="submit" variant="primary" class="w-full py-3 text-base">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    Send Encrypted Inquiry
                                </x-button>
                            </div>
                        </form>
                    </x-card>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

