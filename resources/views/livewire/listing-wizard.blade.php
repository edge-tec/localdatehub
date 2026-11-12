<div class="bg-white rounded-3xl shadow-2xl p-6 sm:p-10 border border-slate-100 text-slate-800">
    
    <!-- Top 6-Step Horizontal Progress Stepper Matching Screenshot -->
    <div class="max-w-3xl mx-auto mb-10">
        <div class="flex items-center justify-between relative">
            <!-- Background connecting line -->
            <div class="absolute left-6 right-6 top-4 h-0.5 bg-slate-200 -z-0"></div>
            <!-- Dynamic active progress line -->
            <div 
                class="absolute left-6 top-4 h-0.5 bg-[#F20B83] transition-all duration-300 -z-0"
                style="width: {{ (($this->visualStep - 1) / 5) * 100 }}%;"
            ></div>

            @php
                $steps = [
                    1 => ['name' => 'Category', 'icon' => '1'],
                    2 => ['name' => 'Details', 'icon' => '2'],
                    3 => ['name' => 'Location', 'icon' => '3'],
                    4 => ['name' => 'Photos', 'icon' => '4'],
                    5 => ['name' => 'Contact', 'icon' => '5'],
                    6 => ['name' => 'Preview', 'icon' => '6'],
                ];
            @endphp

            @foreach($steps as $sNum => $step)
                <div class="flex flex-col items-center relative z-10 cursor-pointer" wire:click="goToVisualStep({{ $sNum }})">
                    <div 
                        class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm transition-all duration-200 {{ $this->visualStep >= $sNum ? 'bg-[#F20B83] text-white ring-4 ring-[#F20B83]/20 shadow-md' : 'bg-slate-200 text-slate-500 hover:bg-slate-300' }}"
                    >
                        @if($this->visualStep > $sNum)
                            <svg class="w-4 h-4 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        @else
                            {{ $sNum }}
                        @endif
                    </div>
                    <span class="text-xs font-semibold mt-2 transition-colors {{ $this->visualStep === $sNum ? 'text-[#F20B83] font-bold' : ($this->visualStep > $sNum ? 'text-slate-800' : 'text-slate-400') }}">
                        {{ $step['name'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Error Alerts -->
    @if($errorMessage)
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-700 flex items-start space-x-2.5">
            <svg class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <strong class="font-bold">Notice:</strong> {{ $errorMessage }}
            </div>
        </div>
    @endif

    @if($autosaveStatus)
        <div class="mb-4 text-right text-xs text-emerald-600 font-medium flex items-center justify-end space-x-1">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>{{ $autosaveStatus }}</span>
        </div>
    @endif

    <!-- STEP 1: Select Category (Matching Screenshot 4x2 Cards) -->
    @if($this->visualStep === 1)
        <div class="space-y-6">
            <div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Select Category</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Choose the right category for your ad</p>
            </div>

            @php
                $categoryCards = [
                    [
                        'id' => 1,
                        'name' => 'Men Seeking Women',
                        'code' => '(M4W)',
                        'icon' => 'heart',
                    ],
                    [
                        'id' => 3,
                        'name' => 'Men Seeking Men',
                        'code' => '(M4M)',
                        'icon' => 'users',
                    ],
                    [
                        'id' => 18,
                        'name' => 'Men Seeking Couples',
                        'code' => '',
                        'icon' => 'user-group',
                    ],
                    [
                        'id' => 2,
                        'name' => 'Women Seeking Men',
                        'code' => '(W4M)',
                        'icon' => 'female',
                    ],
                    [
                        'id' => 4,
                        'name' => 'Women Seeking Women',
                        'code' => '(W4W)',
                        'icon' => 'female-pair',
                    ],
                    [
                        'id' => 19,
                        'name' => 'Women Seeking Couples',
                        'code' => '',
                        'icon' => 'user-group',
                    ],
                    [
                        'id' => 10,
                        'name' => 'Couples Seeking Men',
                        'code' => '',
                        'icon' => 'user-group',
                    ],
                    [
                        'id' => 9,
                        'name' => 'Couples Seeking Women',
                        'code' => '',
                        'icon' => 'user-group',
                    ],
                ];
            @endphp

            <!-- 8 Category Cards in 4x2 Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($categoryCards as $card)
                    @php
                        $isSelected = ($selectedCategoryId == $card['id']);
                    @endphp
                    <div 
                        wire:click="selectCategory({{ $card['id'] }})"
                        class="relative rounded-2xl p-5 border-2 transition-all cursor-pointer flex flex-col items-center justify-center text-center group min-h-[120px] {{ $isSelected ? 'border-[#F20B83] bg-pink-50/40 shadow-sm' : 'border-slate-200 bg-white hover:border-[#F20B83]/50 hover:bg-slate-50' }}"
                    >
                        <!-- Top-Right Checkmark Badge for Selected Card -->
                        @if($isSelected)
                            <div class="absolute top-2.5 right-2.5 w-5 h-5 rounded-full bg-[#F20B83] text-white flex items-center justify-center shadow">
                                <svg class="w-3 h-3 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </div>
                        @endif

                        <!-- Category Icon (Hot Pink) -->
                        <div class="text-[#F20B83] mb-2 group-hover:scale-110 transition duration-200">
                            @if($card['icon'] === 'heart')
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                </svg>
                            @elseif($card['icon'] === 'female')
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14a5 5 0 100-10 5 5 0 000 10zm0 0v7m-3-3h6" />
                                </svg>
                            @elseif($card['icon'] === 'female-pair')
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            @else
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.999-3.199a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                </svg>
                            @endif
                        </div>

                        <!-- Card Label -->
                        <div class="text-xs sm:text-sm font-bold text-slate-800 leading-snug">
                            {{ $card['name'] }}
                            @if($card['code'])
                                <span class="block text-[11px] font-semibold text-slate-500 mt-0.5">{{ $card['code'] }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Hidden radio inputs for test and legacy compatibility -->
            <div class="hidden">
                <input type="radio" wire:model.live="selectedGroupId" value="{{ $selectedGroupId }}" />
                <input type="radio" wire:model.live="selectedCategoryId" value="{{ $selectedCategoryId }}" />
            </div>
            @error('selectedCategoryId') <span class="text-xs text-rose-500 block">{{ $message }}</span> @enderror
            @error('selectedGroupId') <span class="text-xs text-rose-500 block">{{ $message }}</span> @enderror

            <!-- Privacy Alert Box Matching Screenshot -->
            <div class="bg-[#FFF0F6] border border-[#FAD2E1] rounded-2xl p-4 flex items-center space-x-3 text-xs sm:text-sm text-slate-700">
                <div class="w-8 h-8 rounded-full bg-[#F20B83]/15 text-[#F20B83] flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                </div>
                <div class="font-medium">
                    Your privacy is our priority. All listings are moderated and secure.
                </div>
            </div>

            <!-- Bottom Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <a href="{{ route('home') }}" class="text-xs sm:text-sm font-semibold text-slate-500 hover:text-slate-800 flex items-center space-x-1">
                    <span>✕</span>
                    <span>Cancel</span>
                </a>
                <button 
                    type="button" 
                    wire:click="nextWizardStep" 
                    class="bg-[#F20B83] hover:bg-[#d90858] text-white px-7 py-2.5 rounded-full font-bold text-sm shadow-md transition flex items-center space-x-2"
                >
                    <span>Next Step</span>
                    <span>→</span>
                </button>
            </div>
        </div>
    @endif

    <!-- STEP 2: Details (Visual Step 2) -->
    @if($this->visualStep === 2)
        <div class="space-y-7">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-4 border-b border-slate-100">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-pink-50 border border-pink-200/80 text-[#F20B83] text-[10px] font-extrabold uppercase tracking-wider mb-1.5">
                        Step 2 of 6 • Personal Profile &amp; Story
                    </span>
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight">Ad Details</h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Craft an engaging headline and tell local matches what you are looking for</p>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Title / Headline -->
                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/40 p-4 sm:p-5 space-y-2.5 transition focus-within:border-[#F20B83]/60 focus-within:bg-white focus-within:shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <label class="text-xs font-extrabold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                            <span>Listing Headline / Title</span>
                            <span class="text-[#F20B83]">*</span>
                        </label>
                        @php $titleLen = mb_strlen((string) $title); @endphp
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $titleLen >= 10 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                            {{ $titleLen }}/120
                        </span>
                    </div>

                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="title"
                            maxlength="120"
                            placeholder="e.g., Warm & Genuine Professional Seeking Meaningful Connection"
                            class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 bg-white text-slate-900 text-sm font-medium placeholder:text-slate-400 focus:border-[#F20B83] focus:ring-4 focus:ring-[#F20B83]/10 outline-none transition"
                        />
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                        <span>Be concise and descriptive. No phone numbers or external URLs in title.</span>
                        @if($titleLen > 0 && $titleLen < 10)
                            <span class="text-amber-600 font-semibold">Add {{ 10 - $titleLen }} more chars</span>
                        @elseif($titleLen >= 10)
                            <span class="text-emerald-600 font-semibold">✓ Great headline</span>
                        @endif
                    </div>
                    @error('title')
                        <div class="flex items-center gap-1.5 text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 rounded-lg px-3 py-1.5">
                            <span>⚠</span>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <!-- Age & Gender Row -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                    <!-- Interactive Age Stepper (18+) -->
                    <div class="lg:col-span-4 rounded-2xl border border-slate-200/80 bg-slate-50/40 p-4 sm:p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">
                                Your Age (18+) <span class="text-[#F20B83]">*</span>
                            </label>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-pink-50 text-[#F20B83] border border-pink-200">18+ Only</span>
                        </div>

                        <div
                            x-data="{
                                dec() { let v = parseInt($wire.poster_age || 25, 10); if (v > 18) $wire.set('poster_age', v - 1); },
                                inc() { let v = parseInt($wire.poster_age || 25, 10); if (v < 99) $wire.set('poster_age', v + 1); }
                            }"
                            class="flex items-center gap-2 bg-white border border-slate-200 rounded-xl p-1.5 shadow-2xs focus-within:border-[#F20B83] focus-within:ring-4 focus-within:ring-[#F20B83]/10 transition"
                        >
                            <button
                                type="button"
                                @click="dec()"
                                class="w-10 h-10 rounded-lg bg-slate-100 hover:bg-pink-50 hover:text-[#F20B83] text-slate-700 font-black text-lg flex items-center justify-center transition cursor-pointer"
                                aria-label="Decrease age"
                            >−</button>
                            <input
                                type="number"
                                wire:model.live.debounce.200ms="poster_age"
                                min="18"
                                max="99"
                                class="w-full text-center font-black text-lg text-slate-900 bg-transparent border-0 focus:ring-0 outline-none"
                            />
                            <button
                                type="button"
                                @click="inc()"
                                class="w-10 h-10 rounded-lg bg-slate-100 hover:bg-pink-50 hover:text-[#F20B83] text-slate-700 font-black text-lg flex items-center justify-center transition cursor-pointer"
                                aria-label="Increase age"
                            >+</button>
                        </div>
                        <p class="text-[11px] text-slate-500">Must be 18 to 99 years old.</p>
                        @error('poster_age')
                            <span class="text-xs font-semibold text-rose-600 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Interactive Gender Pill Cards -->
                    <div class="lg:col-span-8 rounded-2xl border border-slate-200/80 bg-slate-50/40 p-4 sm:p-5 space-y-3">
                        <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider">
                            Your Gender <span class="text-[#F20B83]">*</span>
                        </label>

                        @php
                            $genderOptions = [
                                ['value' => 'Male', 'label' => 'Male', 'icon' => '👨'],
                                ['value' => 'Female', 'label' => 'Female', 'icon' => '👩'],
                                ['value' => 'Non-Binary', 'label' => 'Non-Binary / Trans', 'icon' => '⚧️'],
                                ['value' => 'Couple', 'label' => 'Couple', 'icon' => '💑'],
                                ['value' => 'Other', 'label' => 'Other', 'icon' => '✨'],
                            ];
                        @endphp

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                            @foreach($genderOptions as $gOpt)
                                @php $gActive = ($poster_gender === $gOpt['value']); @endphp
                                <button
                                    type="button"
                                    wire:click="$set('poster_gender', '{{ $gOpt['value'] }}')"
                                    class="relative flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl border-2 text-left transition cursor-pointer {{ $gActive ? 'border-[#F20B83] bg-pink-50/70 text-slate-900 shadow-xs' : 'border-slate-200 bg-white text-slate-700 hover:border-[#F20B83]/40 hover:bg-slate-50' }}"
                                >
                                    <span class="text-base leading-none">{{ $gOpt['icon'] }}</span>
                                    <span class="text-xs font-bold leading-tight truncate">{{ $gOpt['label'] }}</span>
                                    @if($gActive)
                                        <span class="ml-auto w-4 h-4 rounded-full bg-[#F20B83] text-white text-[10px] flex items-center justify-center shrink-0">✓</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                        @error('poster_gender')
                            <span class="text-xs font-semibold text-rose-600 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Interactive Seeking Intent Cards -->
                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/40 p-4 sm:p-5 space-y-3">
                    <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider">
                        Seeking Intent <span class="text-[#F20B83]">*</span>
                    </label>

                    @php
                        $intentOptions = [
                            ['value' => 'dating', 'label' => 'Dating & Romance', 'desc' => 'Dates, chemistry & genuine sparks', 'icon' => '💘'],
                            ['value' => 'long-term', 'label' => 'Long-Term Relationship', 'desc' => 'Committed partner & future together', 'icon' => '💍'],
                            ['value' => 'activity', 'label' => 'Activity & Travel Partner', 'desc' => 'Events, dinners, trips & hobbies', 'icon' => '✈️'],
                            ['value' => 'casual', 'label' => 'Casual Dating', 'desc' => 'Relaxed, no-pressure meetups', 'icon' => '🥂'],
                            ['value' => 'friendship', 'label' => 'Friendship & Social', 'desc' => 'Local social circle & platonic hangs', 'icon' => '🤝'],
                        ];
                    @endphp

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                        @foreach($intentOptions as $iOpt)
                            @php $iActive = ($intent === $iOpt['value']); @endphp
                            <button
                                type="button"
                                wire:click="$set('intent', '{{ $iOpt['value'] }}')"
                                class="relative flex items-start gap-3 p-3 rounded-xl border-2 text-left transition cursor-pointer {{ $iActive ? 'border-[#F20B83] bg-pink-50/70 shadow-xs' : 'border-slate-200 bg-white hover:border-[#F20B83]/40 hover:bg-slate-50' }}"
                            >
                                <span class="text-lg leading-none mt-0.5">{{ $iOpt['icon'] }}</span>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-extrabold text-slate-900 leading-snug">{{ $iOpt['label'] }}</div>
                                    <div class="text-[11px] text-slate-500 leading-tight mt-0.5">{{ $iOpt['desc'] }}</div>
                                </div>
                                @if($iActive)
                                    <span class="w-4 h-4 rounded-full bg-[#F20B83] text-white text-[10px] flex items-center justify-center shrink-0">✓</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                    @error('intent')
                        <span class="text-xs font-semibold text-rose-600 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Description -->
                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/40 p-4 sm:p-5 space-y-2.5 transition focus-within:border-[#F20B83]/60 focus-within:bg-white focus-within:shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <label class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">
                            Ad Description <span class="text-[#F20B83]">*</span>
                        </label>
                        @php $descLen = mb_strlen((string) $description); @endphp
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $descLen >= 20 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                            {{ $descLen }}/5000
                        </span>
                    </div>

                    <textarea
                        wire:model.live.debounce.250ms="description"
                        rows="6"
                        maxlength="5000"
                        placeholder="Describe who you are, what activities you enjoy, and what you are looking for in a match..."
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-slate-900 text-sm leading-relaxed placeholder:text-slate-400 focus:border-[#F20B83] focus:ring-4 focus:ring-[#F20B83]/10 outline-none transition"
                    ></textarea>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-[11px] text-slate-500">
                        <span>Minimum 20 characters. Commercial solicitations and prostitution are strictly prohibited.</span>
                        @if($descLen < 20)
                            <span class="text-amber-600 font-semibold shrink-0">{{ 20 - $descLen }} more characters needed</span>
                        @else
                            <span class="text-emerald-600 font-semibold shrink-0">✓ Description ready</span>
                        @endif
                    </div>
                    @error('description')
                        <div class="flex items-center gap-1.5 text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 rounded-lg px-3 py-1.5">
                            <span>⚠</span>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>
            </div>

            <!-- Bottom Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <button
                    type="button"
                    wire:click="previousWizardStep"
                    class="px-4 py-2.5 rounded-full border border-slate-200 text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition flex items-center space-x-1.5 cursor-pointer"
                >
                    <span>←</span>
                    <span>Previous</span>
                </button>
                <button
                    type="button"
                    wire:click="nextWizardStep"
                    class="bg-[#F20B83] hover:bg-[#d90858] text-white px-7 py-2.5 rounded-full font-bold text-sm shadow-md transition flex items-center space-x-2 cursor-pointer"
                >
                    <span>Next Step</span>
                    <span>→</span>
                </button>
            </div>
        </div>
    @endif

    <!-- STEP 3: Location (Visual Step 3) -->
    @if($this->visualStep === 3)
        <div class="space-y-7">
            <div class="pb-4 border-b border-slate-100">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-pink-50 border border-pink-200/80 text-[#F20B83] text-[10px] font-extrabold uppercase tracking-wider mb-1.5">
                    Step 3 of 6 • Local Discovery
                </span>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Location Information</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Select your region and city to connect with verified local matches</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Searchable State / Region Selector -->
                <div
                    x-data="{
                        open: false,
                        search: '',
                        get filtered() {
                            const q = this.search.trim().toLowerCase();
                            return Array.from(this.$refs.nativeState.options).filter(o => o.value && (!q || o.text.toLowerCase().includes(q)));
                        },
                        selectedLabel() {
                            const sel = this.$refs.nativeState.selectedOptions[0];
                            return sel && sel.value ? sel.text : 'Select a State / Region';
                        }
                    }"
                    @click.outside="open = false"
                    class="rounded-2xl border border-slate-200/80 bg-slate-50/40 p-4 space-y-2 relative"
                >
                    <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider">
                        State / Region / Province <span class="text-[#F20B83]">*</span>
                    </label>

                    <select x-ref="nativeState" wire:model.live="state_id" class="sr-only" aria-hidden="true">
                        <option value="">Select a State / Region</option>
                        @foreach($states as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code }})</option>
                        @endforeach
                    </select>

                    <button
                        type="button"
                        @click="open = !open; $nextTick(() => { if (open) $refs.stateSearch.focus(); })"
                        class="w-full flex items-center justify-between gap-2 px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-left text-sm font-semibold text-slate-800 hover:border-[#F20B83]/50 focus:border-[#F20B83] focus:ring-4 focus:ring-[#F20B83]/10 transition cursor-pointer"
                    >
                        <span class="flex items-center gap-2 truncate">
                            <span class="text-[#F20B83]">📍</span>
                            <span class="truncate" x-text="selectedLabel()"></span>
                        </span>
                        <svg class="w-4 h-4 text-slate-400 shrink-0 transition" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <div
                        x-show="open"
                        x-transition
                        class="absolute left-4 right-4 top-full mt-1 z-30 rounded-2xl border border-slate-200 bg-white shadow-xl p-2 space-y-2"
                        style="display: none;"
                    >
                        <input
                            x-ref="stateSearch"
                            x-model="search"
                            type="search"
                            placeholder="Search state or region..."
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 focus:border-[#F20B83] outline-none"
                        />
                        <div class="max-h-52 overflow-y-auto space-y-1">
                            <template x-for="opt in filtered" :key="opt.value">
                                <button
                                    type="button"
                                    @click="$wire.set('state_id', opt.value); open = false; search = '';"
                                    class="w-full text-left px-3 py-2 rounded-xl text-xs font-semibold transition cursor-pointer flex items-center justify-between"
                                    :class="String($wire.state_id) === String(opt.value) ? 'bg-[#F20B83] text-white' : 'text-slate-700 hover:bg-pink-50 hover:text-[#F20B83]'"
                                >
                                    <span x-text="opt.text"></span>
                                    <span x-show="String($wire.state_id) === String(opt.value)">✓</span>
                                </button>
                            </template>
                            <div x-show="filtered.length === 0" class="text-xs text-slate-400 px-3 py-2">No matching region.</div>
                        </div>
                    </div>
                    @error('state_id') <span class="text-xs font-semibold text-rose-600 block">{{ $message }}</span> @enderror
                </div>

                <!-- Searchable City Selector -->
                <div
                    x-data="{
                        open: false,
                        search: '',
                        get filtered() {
                            const q = this.search.trim().toLowerCase();
                            return Array.from(this.$refs.nativeCity.options).filter(o => o.value && (!q || o.text.toLowerCase().includes(q)));
                        },
                        selectedLabel() {
                            const sel = this.$refs.nativeCity.selectedOptions[0];
                            return sel && sel.value ? sel.text : 'Select a City';
                        }
                    }"
                    @click.outside="open = false"
                    class="rounded-2xl border border-slate-200/80 bg-slate-50/40 p-4 space-y-2 relative"
                >
                    <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider">
                        City <span class="text-[#F20B83]">*</span>
                    </label>

                    <select x-ref="nativeCity" wire:model.live="city_id" class="sr-only" aria-hidden="true">
                        <option value="">Select a City</option>
                        @foreach($cities as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>

                    <button
                        type="button"
                        @click="open = !open; $nextTick(() => { if (open) $refs.citySearch.focus(); })"
                        class="w-full flex items-center justify-between gap-2 px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-left text-sm font-semibold text-slate-800 hover:border-[#F20B83]/50 focus:border-[#F20B83] focus:ring-4 focus:ring-[#F20B83]/10 transition cursor-pointer"
                    >
                        <span class="flex items-center gap-2 truncate">
                            <span class="text-[#F20B83]">🏙️</span>
                            <span class="truncate" x-text="selectedLabel()"></span>
                        </span>
                        <svg class="w-4 h-4 text-slate-400 shrink-0 transition" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <div
                        x-show="open"
                        x-transition
                        class="absolute left-4 right-4 top-full mt-1 z-30 rounded-2xl border border-slate-200 bg-white shadow-xl p-2 space-y-2"
                        style="display: none;"
                    >
                        <input
                            x-ref="citySearch"
                            x-model="search"
                            type="search"
                            placeholder="Search city..."
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-slate-800 focus:border-[#F20B83] outline-none"
                        />
                        <div class="max-h-52 overflow-y-auto space-y-1">
                            <template x-for="opt in filtered" :key="opt.value">
                                <button
                                    type="button"
                                    @click="$wire.set('city_id', opt.value); open = false; search = '';"
                                    class="w-full text-left px-3 py-2 rounded-xl text-xs font-semibold transition cursor-pointer flex items-center justify-between"
                                    :class="String($wire.city_id) === String(opt.value) ? 'bg-[#F20B83] text-white' : 'text-slate-700 hover:bg-pink-50 hover:text-[#F20B83]'"
                                >
                                    <span x-text="opt.text"></span>
                                    <span x-show="String($wire.city_id) === String(opt.value)">✓</span>
                                </button>
                            </template>
                            <div x-show="filtered.length === 0" class="text-xs text-slate-400 px-3 py-2">Select a state/region first or refine search.</div>
                        </div>
                    </div>
                    @error('city_id') <span class="text-xs font-semibold text-rose-600 block">{{ $message }}</span> @enderror
                </div>

                <!-- Neighborhood / Area -->
                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/40 p-4 space-y-2 focus-within:border-[#F20B83]/60 focus-within:bg-white transition">
                    <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider">
                        Neighborhood / Area <span class="text-slate-400 font-semibold normal-case">(Optional)</span>
                    </label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="neighborhood"
                        placeholder="e.g. Downtown, Midtown, Soho, Bondi"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-900 text-sm focus:border-[#F20B83] focus:ring-4 focus:ring-[#F20B83]/10 outline-none transition"
                    />
                </div>

                <!-- Cross Streets -->
                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/40 p-4 space-y-2 focus-within:border-[#F20B83]/60 focus-within:bg-white transition">
                    <label class="block text-xs font-extrabold text-slate-800 uppercase tracking-wider">
                        Cross Streets <span class="text-slate-400 font-semibold normal-case">(Optional)</span>
                    </label>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="cross_streets"
                        placeholder="e.g. 6th St &amp; Main Ave"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-900 text-sm focus:border-[#F20B83] focus:ring-4 focus:ring-[#F20B83]/10 outline-none transition"
                    />
                </div>
            </div>

            <!-- Privacy Distance Toggle -->
            <div class="bg-gradient-to-r from-slate-50 to-pink-50/40 border border-slate-200 rounded-2xl p-4 sm:p-5 flex items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#F20B83]/10 text-[#F20B83] flex items-center justify-center shrink-0">
                        🛡️
                    </div>
                    <div>
                        <h4 class="text-xs sm:text-sm font-extrabold text-slate-900">Obfuscate Exact Location</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Only display your city and general area. Protects your personal privacy from triangulation.</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                    <input type="checkbox" wire:model="hide_exact_distance" class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#F20B83]"></div>
                </label>
            </div>

            <!-- Bottom Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <button
                    type="button"
                    wire:click="previousWizardStep"
                    class="px-4 py-2.5 rounded-full border border-slate-200 text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition flex items-center space-x-1.5 cursor-pointer"
                >
                    <span>←</span>
                    <span>Previous</span>
                </button>
                <button
                    type="button"
                    wire:click="nextWizardStep"
                    class="bg-[#F20B83] hover:bg-[#d90858] text-white px-7 py-2.5 rounded-full font-bold text-sm shadow-md transition flex items-center space-x-2 cursor-pointer"
                >
                    <span>Next Step</span>
                    <span>→</span>
                </button>
            </div>
        </div>
    @endif

    <!-- STEP 4: Photos (Visual Step 4) -->
    @if($this->visualStep === 4)
        <div class="space-y-6">
            <div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Photos & Media</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Upload clear photos to get up to 5x more responses</p>
            </div>

            <!-- Drag & Drop Upload Zone -->
            <div 
                x-data="{ isDropping: false }"
                class="border-2 border-dashed rounded-3xl p-8 text-center transition-all"
                :class="isDropping ? 'border-[#F20B83] bg-pink-50/50' : 'border-slate-200 bg-slate-50/50 hover:border-slate-300'"
                @dragover.prevent="isDropping = true"
                @dragleave.prevent="isDropping = false"
                @drop.prevent="isDropping = false"
            >
                <div class="max-w-md mx-auto flex flex-col items-center">
                    <div class="w-14 h-14 rounded-2xl bg-[#F20B83]/15 text-[#F20B83] flex items-center justify-center mb-3">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                        </svg>
                    </div>
                    <label class="cursor-pointer">
                        <span class="bg-[#F20B83] hover:bg-[#d90858] text-white px-5 py-2 rounded-full font-bold text-xs shadow-md transition inline-block">
                            Choose Photos
                        </span>
                        <input type="file" wire:model="newPhotos" multiple class="hidden" accept="image/jpeg,image/png,image/webp">
                    </label>
                    <p class="text-xs text-slate-500 mt-2">JPG, PNG, or WebP. Max 5MB per photo. EXIF location metadata scrubbed automatically.</p>
                </div>
            </div>

            <!-- Uploaded Photos Grid -->
            @if(count($uploadedMedia) > 0)
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach($uploadedMedia as $m)
                        <div class="relative rounded-2xl overflow-hidden border border-slate-200 shadow group aspect-square bg-slate-900 flex items-center justify-center isolate">
                            <img src="{{ $m['thumb'] }}" alt="Photo" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='{{ asset('images/featured-1.jpg') }}';" class="max-w-full max-h-full w-auto h-auto object-contain">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center space-x-2">
                                @if(!$m['is_primary'])
                                    <button 
                                        type="button" 
                                        wire:click="setPrimaryPhoto({{ $m['id'] }})"
                                        class="bg-white/90 hover:bg-white text-slate-900 text-[10px] font-bold px-2 py-1 rounded shadow"
                                    >
                                        Set Primary
                                    </button>
                                @endif
                                <button 
                                    type="button" 
                                    wire:click="removePhoto({{ $m['id'] }})"
                                    class="bg-rose-600 hover:bg-rose-700 text-white text-[10px] font-bold px-2 py-1 rounded shadow"
                                >
                                    Delete
                                </button>
                            </div>
                            @if($m['is_primary'])
                                <span class="absolute top-2 left-2 bg-[#F20B83] text-white text-[10px] font-extrabold px-2 py-0.5 rounded-full shadow">
                                    Primary
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Bottom Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <button 
                    type="button" 
                    wire:click="previousWizardStep" 
                    class="text-xs sm:text-sm font-semibold text-slate-500 hover:text-slate-800 flex items-center space-x-1"
                >
                    <span>←</span>
                    <span>Previous</span>
                </button>
                <button 
                    type="button" 
                    wire:click="nextWizardStep" 
                    class="bg-[#F20B83] hover:bg-[#d90858] text-white px-7 py-2.5 rounded-full font-bold text-sm shadow-md transition flex items-center space-x-2"
                >
                    <span>Next Step</span>
                    <span>→</span>
                </button>
            </div>
        </div>
    @endif

    <!-- STEP 5: Contact Preferences (Visual Step 5) -->
    @if($this->visualStep === 5)
        <div class="space-y-6">
            <div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Contact & Privacy</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Choose how interested members can get in touch with you</p>
            </div>

            <div class="space-y-4">
                <!-- Platform Messages -->
                <div class="p-4 rounded-2xl border border-slate-200 flex items-center justify-between bg-slate-50">
                    <div>
                        <h4 class="text-xs sm:text-sm font-bold text-slate-800">Allow Platform Messaging</h4>
                        <p class="text-xs text-slate-500">Receive private, secure messages directly through LocalDateHub.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="allow_platform_messages" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#F20B83]"></div>
                    </label>
                </div>

                <!-- Email Visibility & Privacy Control (Encrypted vs Public) -->
                <div class="p-5 rounded-2xl border border-slate-200 space-y-4 bg-white shadow-2xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
                        <div>
                            <h4 class="text-xs sm:text-sm font-black text-slate-900 flex items-center gap-2">
                                <span>✉️ Email Address Visibility (Public or Encrypted)</span>
                                @if($show_email)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">🌐 Public on Post</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-indigo-50 text-indigo-700 border border-indigo-200">🔒 Encrypted &amp; Masked</span>
                                @endif
                            </h4>
                            <p class="text-xs text-slate-500 mt-0.5">Choose whether anyone viewing your post can see your email publicly, or keep it encrypted behind our privacy relay.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Option A: Encrypted / Masked -->
                        <button
                            type="button"
                            wire:click="$set('show_email', false)"
                            class="text-left p-3.5 rounded-xl border-2 transition cursor-pointer {{ !$show_email ? 'border-[#F20B83] bg-pink-50/40' : 'border-slate-200 bg-slate-50 hover:border-slate-300' }}"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-slate-900">🔒 Keep Email Encrypted</span>
                                <span class="w-4 h-4 rounded-full border flex items-center justify-center {{ !$show_email ? 'border-[#F20B83] bg-[#F20B83] text-white' : 'border-slate-300 bg-white' }}">
                                    @if(!$show_email) ✓ @endif
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-1">Hides your real email and displays a masked relay address (e.g. <code class="font-mono text-[10px]">relay-xxxx@localdatehub.com</code>).</p>
                        </button>

                        <!-- Option B: Public Email -->
                        <button
                            type="button"
                            wire:click="$set('show_email', true)"
                            class="text-left p-3.5 rounded-xl border-2 transition cursor-pointer {{ $show_email ? 'border-emerald-500 bg-emerald-50/40' : 'border-slate-200 bg-slate-50 hover:border-slate-300' }}"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-slate-900">🌐 Make Email Public</span>
                                <span class="w-4 h-4 rounded-full border flex items-center justify-center {{ $show_email ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-300 bg-white' }}">
                                    @if($show_email) ✓ @endif
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-1">Displays your email address publicly on the post so anyone can view and email you directly.</p>
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Contact Email for This Post
                        </label>
                        <input
                            type="email"
                            wire:model="preferred_email"
                            placeholder="{{ auth()->user()?->email ?? 'you@example.com' }}"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-slate-900 text-sm focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] outline-none"
                        />
                        <p class="text-[11px] text-slate-500 mt-1">
                            @if($show_email)
                                <span class="text-emerald-700 font-bold">🌐 Public Mode Active:</span> This email will be visible to everyone on your published post.
                            @else
                                <span class="text-indigo-700 font-bold">🔒 Encrypted Mode Active:</span> Stored encrypted in our database and hidden behind an anonymous relay on your post.
                            @endif
                        </p>
                    </div>

                    @if(!$show_email)
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">Enable Anonymous Email Relay Address</span>
                                <span class="text-[11px] text-slate-500">Show a masked proxy email on your post while keeping your real email encrypted.</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="allow_email_relay" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#F20B83]"></div>
                            </label>
                        </div>
                    @endif
                </div>

                <!-- Phone Preferences -->
                <div class="p-4 rounded-2xl border border-slate-200 space-y-3 bg-white">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Phone Number (Optional & Encrypted)
                        </label>
                        <input 
                            type="tel" 
                            wire:model="preferred_phone" 
                            placeholder="(555) 123-4567" 
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-slate-900 text-sm focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] outline-none"
                        />
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-slate-600 font-medium">Display phone number publicly on ad page</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="show_phone" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#F20B83]"></div>
                        </label>
                    </div>
                </div>

                <!-- Contact Instructions -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Special Contact Instructions (Optional)
                    </label>
                    <input 
                        type="text" 
                        wire:model="contact_instructions" 
                        placeholder="e.g. Text first, prefer messaging after 7 PM" 
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-slate-900 text-sm focus:border-[#F20B83] focus:ring-1 focus:ring-[#F20B83] outline-none"
                    />
                </div>
            </div>

            <!-- Bottom Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                <button 
                    type="button" 
                    wire:click="previousWizardStep" 
                    class="text-xs sm:text-sm font-semibold text-slate-500 hover:text-slate-800 flex items-center space-x-1"
                >
                    <span>←</span>
                    <span>Previous</span>
                </button>
                <button 
                    type="button" 
                    wire:click="nextWizardStep" 
                    class="bg-[#F20B83] hover:bg-[#d90858] text-white px-7 py-2.5 rounded-full font-bold text-sm shadow-md transition flex items-center space-x-2"
                >
                    <span>Next Step</span>
                    <span>→</span>
                </button>
            </div>
        </div>
    @endif

    <!-- STEP 6: Preview & Publish (Visual Step 6) -->
    @if($this->visualStep === 6)
        <div class="space-y-6">
            <div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Preview & Publish</h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Review your personal advertisement and certify compliance before publishing</p>
            </div>

            <!-- Live Ad Preview Card -->
            <div class="rounded-2xl border-2 border-[#F20B83]/30 bg-gradient-to-br from-pink-50/20 via-white to-slate-50 p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <span class="inline-block px-2.5 py-0.5 rounded-full bg-[#F20B83] text-white text-[10px] font-extrabold uppercase tracking-wider mb-2">
                            Ad Preview
                        </span>
                        <h3 class="text-xl font-black text-slate-900">{{ $title ?: 'Your Ad Title Here' }}</h3>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mt-1">
                            <span>Age: <strong>{{ $poster_age }}</strong></span>
                            <span>•</span>
                            <span>Gender: <strong>{{ $poster_gender }}</strong></span>
                            <span>•</span>
                            <span>Intent: <strong>{{ ucfirst($intent) }}</strong></span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-200 text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line">
                    {{ $description ?: 'Your detailed personal ad description will appear here...' }}
                </div>
            </div>

            <!-- Monetization, Quota & Wallet Balance Summary -->
            @if(isset($quotaInfo) && isset($priceInfo))
                <div class="bg-gradient-to-r from-slate-50 to-pink-50/30 border border-slate-200 rounded-2xl p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                        <div class="flex items-center space-x-2">
                            <span class="text-xl">💳</span>
                            <div>
                                <h4 class="text-xs font-black uppercase tracking-wider text-slate-900">Posting Quota & Payment Breakdown</h4>
                                <p class="text-[11px] text-slate-500">Authoritative fee based on your account status and category</p>
                            </div>
                        </div>
                        <span class="text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-full bg-slate-200/80 text-slate-700 tracking-wider">
                            {{ ucfirst(str_replace('_', ' ', $quotaInfo['account_type'])) }} Account
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div class="bg-white p-3 rounded-xl border border-slate-100 shadow-2xs">
                            <span class="text-slate-500 text-[11px] block">Posting Fee:</span>
                            <span class="text-sm font-black {{ $priceInfo['is_free'] ? 'text-emerald-600' : 'text-[#F20B83]' }}">
                                {{ $priceInfo['is_free'] ? 'FREE' : $priceInfo['price_credits'] . ' Credits' }}
                            </span>
                            <span class="text-[10px] text-slate-400 block truncate">{{ $priceInfo['rule_applied'] }}</span>
                        </div>

                        <div class="bg-white p-3 rounded-xl border border-slate-100 shadow-2xs">
                            <span class="text-slate-500 text-[11px] block">Free Quota Remaining:</span>
                            <span class="text-sm font-black text-slate-900">
                                {{ $quotaInfo['is_unlimited'] ? 'Unlimited' : $quotaInfo['free_remaining'] . ' Posts' }}
                            </span>
                            <span class="text-[10px] text-slate-400 block">This month</span>
                        </div>

                        <div class="bg-white p-3 rounded-xl border border-slate-100 shadow-2xs">
                            <span class="text-slate-500 text-[11px] block">Your Wallet Balance:</span>
                            <span class="text-sm font-black text-slate-900">
                                {{ number_format($walletBalance) }} Credits
                            </span>
                            <span class="text-[10px] text-slate-400 block">Available now</span>
                        </div>

                        <div class="bg-white p-3 rounded-xl border border-slate-100 shadow-2xs">
                            <span class="text-slate-500 text-[11px] block">Balance After Post:</span>
                            @php
                                $afterBalance = $walletBalance - $priceInfo['price_credits'];
                            @endphp
                            <span class="text-sm font-black {{ $afterBalance < 0 ? 'text-rose-600' : 'text-slate-900' }}">
                                {{ number_format(max(0, $afterBalance)) }} Credits
                            </span>
                            <span class="text-[10px] {{ $afterBalance < 0 ? 'text-rose-500 font-bold' : 'text-slate-400' }} block">
                                {{ $afterBalance < 0 ? 'Shortfall: ' . abs($afterBalance) . ' credits' : 'Sufficient' }}
                            </span>
                        </div>
                    </div>

                    <!-- Insufficient Credits Warning Alert -->
                    @if(!$priceInfo['is_free'] && $walletBalance < $priceInfo['price_credits'])
                        <div class="bg-rose-50 border border-rose-200 p-3.5 rounded-xl flex items-center justify-between gap-3 text-xs text-rose-800">
                            <div class="flex items-center space-x-2">
                                <span class="text-base">⚠️</span>
                                <span>Insufficient credits to publish this listing. Please add credits to your wallet.</span>
                            </div>
                            <a 
                                href="{{ route('account.wallet.index') }}" 
                                target="_blank"
                                class="bg-[#F20B83] hover:bg-[#d90858] text-white px-4 py-1.5 rounded-lg font-bold text-xs shrink-0 shadow-xs transition"
                            >
                                Add Credits →
                            </a>
                        </div>
                    @endif

                    <!-- Quota Blocked Warning Alert -->
                    @if(!$quotaInfo['can_post'])
                        <div class="bg-amber-50 border border-amber-200 p-3.5 rounded-xl flex items-center space-x-2 text-xs text-amber-800">
                            <span class="text-base">⛔</span>
                            <span>{{ $quotaInfo['blocked_reason'] }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Compliance Checkboxes Matching US Adult Legal Guidelines -->
            <div class="bg-rose-50/70 border border-rose-200/80 rounded-2xl p-5 space-y-3">
                <h4 class="text-xs font-bold text-rose-900 uppercase tracking-wider">Mandatory Compliance & Verification</h4>

                <label class="flex items-start space-x-3 cursor-pointer">
                    <input type="checkbox" wire:model="confirm_age_18" class="mt-0.5 rounded text-[#F20B83] focus:ring-[#F20B83]">
                    <span class="text-xs text-slate-800">
                        <strong>18+ Age Verification:</strong> I certify under penalty of perjury that I am at least 18 years of age.
                    </span>
                </label>
                @error('confirm_age_18') <span class="text-xs text-rose-600 block ml-6">{{ $message }}</span> @enderror

                <label class="flex items-start space-x-3 cursor-pointer">
                    <input type="checkbox" wire:model="confirm_community_rules" class="mt-0.5 rounded text-[#F20B83] focus:ring-[#F20B83]">
                    <span class="text-xs text-slate-800">
                        <strong>Community Standards:</strong> I agree to adhere to LocalDateHub Terms of Service and Privacy Guidelines.
                    </span>
                </label>
                @error('confirm_community_rules') <span class="text-xs text-rose-600 block ml-6">{{ $message }}</span> @enderror

                <label class="flex items-start space-x-3 cursor-pointer">
                    <input type="checkbox" wire:model="confirm_anti_trafficking" class="mt-0.5 rounded text-[#F20B83] focus:ring-[#F20B83]">
                    <span class="text-xs text-slate-800">
                        <strong>Anti-Trafficking & Legal Notice:</strong> Commercial sexual services, prostitution, human trafficking, and underage content are strictly illegal and prohibited.
                    </span>
                </label>
                @error('confirm_anti_trafficking') <span class="text-xs text-rose-600 block ml-6">{{ $message }}</span> @enderror
            </div>

            <!-- Bottom Actions -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-100">
                <button 
                    type="button" 
                    wire:click="previousWizardStep" 
                    class="text-xs sm:text-sm font-semibold text-slate-500 hover:text-slate-800 flex items-center space-x-1"
                >
                    <span>←</span>
                    <span>Previous</span>
                </button>

                <div class="flex items-center space-x-3 w-full sm:w-auto justify-end">
                    <button 
                        type="button" 
                        wire:click="saveDraft" 
                        class="px-5 py-2.5 rounded-full border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold transition"
                    >
                        Save Draft
                    </button>
                    @php
                        $canSubmit = ($quotaInfo['can_post'] ?? true) && (($priceInfo['is_free'] ?? true) || ($walletBalance >= ($priceInfo['price_credits'] ?? 0)));
                    @endphp
                    @if($canSubmit)
                        <button 
                            type="button" 
                            wire:click="submitForReview" 
                            class="bg-[#F20B83] hover:bg-[#d90858] text-white px-8 py-2.5 rounded-full font-bold text-sm shadow-xl transition flex items-center space-x-2"
                        >
                            <span>Publish Ad</span>
                            <span>🚀</span>
                        </button>
                    @else
                        <button 
                            type="button" 
                            disabled 
                            class="bg-slate-300 text-slate-500 cursor-not-allowed px-8 py-2.5 rounded-full font-bold text-sm shadow-none flex items-center space-x-2"
                            title="Cannot submit: Insufficient balance or posting limit reached"
                        >
                            <span>Publish Ad</span>
                            <span>🔒</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

</div>
