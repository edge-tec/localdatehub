<div class="space-y-6">
    <!-- Top Progress Bar & Autosave Status -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 sm:p-5 shadow-xl">
        <div class="flex items-center justify-between mb-3">
            <div>
                <span class="text-xs font-bold text-rose-500 uppercase tracking-wider">
                    Step {{ $currentStep }} of {{ $totalSteps }}
                </span>
                <h2 class="text-lg font-bold text-white">
                    @switch($currentStep)
                        @case(1) Select Category Group @break
                        @case(2) Choose Specific Subcategory @break
                        @case(3) Headline & Basic Info @break
                        @case(4) Listing Description @break
                        @case(5) Dynamic Category Attributes @break
                        @case(6) Location & Obfuscation @break
                        @case(7) Photos & Visual Verification @break
                        @case(8) External Contact Options @break
                        @case(9) Contact Form Preferences @break
                        @case(10) Complete Listing Preview @break
                        @case(11) Trust & Compliance Confirmation @break
                        @case(12) Final Review & Submission @break
                    @endswitch
                </h2>
            </div>
            <div class="flex items-center space-x-3 text-xs">
                @if($autosaveStatus)
                    <span class="text-emerald-400 flex items-center space-x-1">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>{{ $autosaveStatus }}</span>
                    </span>
                @endif
                <button
                    type="button"
                    wire:click="saveDraft"
                    class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs font-semibold transition"
                >
                    Save Draft
                </button>
            </div>
        </div>

        <!-- Stepper Bar -->
        <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden border border-slate-800">
            <div
                class="bg-gradient-to-r from-rose-600 via-pink-600 to-amber-500 h-full transition-all duration-300"
                style="width: {{ ($currentStep / $totalSteps) * 100 }}%;"
            ></div>
        </div>
    </div>

    @if($errorMessage)
        <div class="p-4 bg-rose-950/60 border border-rose-600/50 rounded-xl text-xs text-rose-200 flex items-start space-x-2.5 shadow-lg">
            <svg class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <strong class="font-bold text-rose-300">Submission Blocked:</strong> {{ $errorMessage }}
            </div>
        </div>
    @endif

    <!-- Wizard Steps Body -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-md">
        <!-- Step 1: Select Category Group -->
        @if($currentStep === 1)
            <div class="space-y-4">
                <p class="text-xs text-slate-400">Choose the high-level connection group for your personal advertisement:</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($categoryGroups as $group)
                        <label
                            class="p-4 rounded-2xl border transition cursor-pointer flex items-start space-x-3 {{ $selectedGroupId === $group->id ? 'bg-rose-500/10 border-rose-500 text-white' : 'bg-slate-950/60 border-slate-800 text-slate-300 hover:border-slate-700' }}"
                        >
                            <input
                                type="radio"
                                name="selectedGroupId"
                                value="{{ $group->id }}"
                                wire:model.live="selectedGroupId"
                                class="mt-1 text-rose-600 focus:ring-rose-500"
                            >
                            <div>
                                <span class="font-bold text-sm block text-white">{{ $group->name }}</span>
                                <span class="text-xs text-slate-400 block mt-1">{{ $group->description }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('selectedGroupId') <span class="text-xs text-rose-400 block">{{ $message }}</span> @enderror
            </div>
        @endif

        <!-- Step 2: Choose Subcategory -->
        @if($currentStep === 2)
            <div class="space-y-4">
                <p class="text-xs text-slate-400">Select the specific subcategory that accurately describes your seeking intent:</p>
                @if($subcategories->isEmpty())
                    <p class="text-xs text-amber-400">Please go back to Step 1 and select a category group first.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($subcategories as $sub)
                            <label
                                class="p-4 rounded-2xl border transition cursor-pointer flex items-start space-x-3 {{ $selectedCategoryId === $sub->id ? 'bg-rose-500/10 border-rose-500 text-white' : 'bg-slate-950/60 border-slate-800 text-slate-300 hover:border-slate-700' }}"
                            >
                                <input
                                    type="radio"
                                    name="selectedCategoryId"
                                    value="{{ $sub->id }}"
                                    wire:model.live="selectedCategoryId"
                                    class="mt-1 text-rose-600 focus:ring-rose-500"
                                >
                                <div>
                                    <div class="flex items-center space-x-1.5">
                                        <span class="font-bold text-sm text-white">{{ $sub->name }}</span>
                                        @if(!empty($sub->code))
                                            <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-slate-800 text-rose-400 font-bold">[{{ $sub->code }}]</span>
                                        @endif
                                    </div>
                                    <span class="text-xs text-slate-400 block mt-1 line-clamp-2">{{ $sub->description }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('selectedCategoryId') <span class="text-xs text-rose-400 block">{{ $message }}</span> @enderror
            </div>
        @endif

        <!-- Step 3: Title & Basic Info -->
        @if($currentStep === 3)
            <div class="space-y-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Listing Headline / Title <span class="text-rose-400">*</span>
                    </label>
                    <input
                        type="text"
                        wire:model="title"
                        placeholder="e.g. Fit 32yo professional seeking adventurous partner for hiking and weekend coffee"
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                    >
                    <span class="text-[11px] text-slate-400 mt-1 block">Character count: {{ strlen($title) }} / 120 (Min 5)</span>
                    @error('title') <span class="text-xs text-rose-400 block mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Your Age (18+) <span class="text-rose-400">*</span>
                        </label>
                        <input
                            type="number"
                            wire:model="poster_age"
                            min="18"
                            max="99"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                        >
                        @error('poster_age') <span class="text-xs text-rose-400 block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Gender Identity <span class="text-rose-400">*</span>
                        </label>
                        <select
                            wire:model="poster_gender"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                        >
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Non-Binary">Non-Binary</option>
                            <option value="Transgender">Transgender</option>
                            <option value="Couple">Couple</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Connection Intent <span class="text-rose-400">*</span>
                        </label>
                        <select
                            wire:model="intent"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                        >
                            <option value="dating">Dating & Romance</option>
                            <option value="long_term">Long-Term Relationship</option>
                            <option value="casual">Casual Coffee & Meets</option>
                            <option value="activity">Activity Companion</option>
                            <option value="friendship">Platonic Friendship</option>
                        </select>
                    </div>
                </div>
            </div>
        @endif

        <!-- Step 4: Description -->
        @if($currentStep === 4)
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Detailed Advertisement Description <span class="text-rose-400">*</span>
                    </label>
                    <textarea
                        wire:model="description"
                        rows="8"
                        placeholder="Tell others about yourself, your lifestyle, what you are looking for in a match, and how you'd like to spend time together..."
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl p-4 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none leading-relaxed"
                    ></textarea>
                    <div class="flex items-center justify-between text-xs text-slate-400 mt-1">
                        <span>Min 20 characters &bull; Max 5,000 characters</span>
                        <span>Length: {{ strlen($description) }} chars</span>
                    </div>
                    @error('description') <span class="text-xs text-rose-400 block mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="p-3 bg-rose-950/20 border border-rose-900/40 rounded-xl text-xs text-rose-300">
                    <strong class="font-bold text-rose-400">Trust & Safety Guidelines:</strong>
                    Do not include financial requests (CashApp, Venmo, crypto), compensation rates, or commercial solicitation. Listings with solicitation terms are automatically rejected.
                </div>
            </div>
        @endif

        <!-- Step 5: Dynamic Category Attributes -->
        @if($currentStep === 5)
            <div class="space-y-4">
                <p class="text-xs text-slate-400">Complete category-specific questions to help local adults find you via search filters:</p>
                @if($categoryAttributes->isEmpty())
                    <p class="text-xs text-slate-400">No additional custom attributes required for this category.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($categoryAttributes as $attr)
                            <div class="p-4 bg-slate-950 border border-slate-800/80 rounded-2xl space-y-1.5">
                                <label class="block text-xs font-semibold text-white">
                                    {{ $attr->name }}
                                    @if($attr->is_required) <span class="text-rose-400">*</span> @endif
                                </label>
                                @if($attr->help_text)
                                    <span class="block text-[11px] text-slate-400">{{ $attr->help_text }}</span>
                                @endif

                                @if(in_array($attr->field_type, ['select', 'radio']))
                                    <select
                                        wire:model="dynamicAttributes.{{ $attr->code }}"
                                        class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-1 focus:ring-rose-500 focus:outline-none"
                                    >
                                        <option value="">-- Choose {{ $attr->name }} --</option>
                                        @foreach($attr->options as $opt)
                                            <option value="{{ $opt->value }}">{{ $opt->label }}</option>
                                        @endforeach
                                    </select>
                                @elseif($attr->field_type === 'number')
                                    <input
                                        type="number"
                                        wire:model="dynamicAttributes.{{ $attr->code }}"
                                        placeholder="{{ $attr->placeholder ?: $attr->name }}"
                                        class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-1 focus:ring-rose-500 focus:outline-none"
                                    >
                                @else
                                    <input
                                        type="text"
                                        wire:model="dynamicAttributes.{{ $attr->code }}"
                                        placeholder="{{ $attr->placeholder ?: $attr->name }}"
                                        class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:ring-1 focus:ring-rose-500 focus:outline-none"
                                    >
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <!-- Step 6: Location Selection -->
        @if($currentStep === 6)
            <div class="space-y-4">
                <p class="text-xs text-slate-400">Choose your geographic hub. Exact GPS coordinates are automatically fuzzed with anti-triangulation offsets to protect your residential privacy.</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            State <span class="text-rose-400">*</span>
                        </label>
                        <select
                            wire:model.live="state_id"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                        >
                            <option value="">-- Select State --</option>
                            @foreach($states as $st)
                                <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->code }})</option>
                            @endforeach
                        </select>
                        @error('state_id') <span class="text-xs text-rose-400 block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            City / Populated Place <span class="text-rose-400">*</span>
                        </label>
                        <select
                            wire:model.live="city_id"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                        >
                            <option value="">-- Select City --</option>
                            @foreach($cities as $ct)
                                <option value="{{ $ct->id }}">{{ $ct->name }}</option>
                            @endforeach
                        </select>
                        @error('city_id') <span class="text-xs text-rose-400 block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Postal ZIP Code (Optional)
                        </label>
                        <select
                            wire:model="zip_code_id"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                        >
                            <option value="">-- General City Area --</option>
                            @foreach($zipCodes as $zip)
                                <option value="{{ $zip->id }}">{{ $zip->code }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Neighborhood / General Area (Optional)
                        </label>
                        <input
                            type="text"
                            wire:model="neighborhood"
                            placeholder="e.g. Downtown, South Congress, Midtown"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Cross Streets (Optional)
                        </label>
                        <input
                            type="text"
                            wire:model="cross_streets"
                            placeholder="e.g. Near 5th & Lamar"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                        >
                    </div>
                </div>

                <div class="pt-2">
                    <label class="flex items-center space-x-2 cursor-pointer text-xs text-slate-300">
                        <input type="checkbox" wire:model="hide_exact_distance" class="rounded border-slate-700 bg-slate-950 text-rose-600 focus:ring-rose-500">
                        <span>Hide approximate mile distance calculation from search results</span>
                    </label>
                </div>
            </div>
        @endif

        <!-- Step 7: Photo Upload -->
        @if($currentStep === 7)
            <div class="space-y-5">
                <div class="p-4 bg-slate-950 border-2 border-dashed border-slate-800 rounded-2xl text-center space-y-2">
                    <svg class="w-8 h-8 text-rose-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <div class="text-xs text-slate-300 font-semibold">
                        Upload Photos (Up to 6 photos &bull; Max 5MB each)
                    </div>
                    <p class="text-[11px] text-slate-400">
                        All images are converted to WebP and stripped of EXIF metadata (GPS location & camera info) automatically.
                    </p>
                    <input
                        type="file"
                        wire:model="newPhotos"
                        multiple
                        accept="image/jpeg,image/png,image/webp"
                        class="block w-full text-xs text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-rose-600 file:text-white hover:file:bg-rose-500 cursor-pointer pt-2"
                    >
                </div>

                @if(!empty($uploadedMedia))
                    <div class="space-y-2">
                        <span class="block text-xs font-semibold text-white uppercase tracking-wider">
                            Attached Photos ({{ count($uploadedMedia) }} / 6)
                        </span>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($uploadedMedia as $item)
                                <div class="relative bg-slate-950 border border-slate-800 rounded-xl overflow-hidden group">
                                    <img src="{{ $item['thumb'] }}" class="w-full h-32 object-cover" alt="Upload">
                                    <div class="p-2 flex items-center justify-between text-[11px] bg-slate-900">
                                        @if($item['is_primary'])
                                            <span class="px-1.5 py-0.5 rounded bg-rose-500/20 text-rose-300 font-bold text-[10px]">Primary</span>
                                        @else
                                            <button type="button" wire:click="setPrimaryPhoto({{ $item['id'] }})" class="text-slate-400 hover:text-white">
                                                Set Primary
                                            </button>
                                        @endif
                                        <button type="button" wire:click="removePhoto({{ $item['id'] }})" class="text-rose-400 hover:text-rose-300 font-bold">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Step 8: External Contact Configuration -->
        @if($currentStep === 8)
            <div class="space-y-4">
                <p class="text-xs text-slate-400">Optionally provide direct contact details. All telephone numbers and messaging handles are stored using AES-256-GCM encryption in our database.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Telephone / SMS (Encrypted)
                        </label>
                        <input
                            type="text"
                            wire:model="preferred_phone"
                            placeholder="e.g. (512) 555-0199"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                        >
                        <span class="text-[11px] text-slate-400 mt-1 block">Never shown publicly unless explicitly enabled.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            External Messaging Handle (Encrypted)
                        </label>
                        <input
                            type="text"
                            wire:model="external_handle"
                            placeholder="e.g. @SignalUser or Telegram handle"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                        >
                    </div>
                </div>

                <div class="pt-2">
                    <label class="flex items-center space-x-2 cursor-pointer text-xs text-slate-300">
                        <input type="checkbox" wire:model="show_phone" class="rounded border-slate-700 bg-slate-950 text-rose-600 focus:ring-rose-500">
                        <span>Display telephone number directly on public listing page</span>
                    </label>
                </div>
            </div>
        @endif

        <!-- Step 9: Contact Preferences -->
        @if($currentStep === 9)
            <div class="space-y-4">
                <p class="text-xs text-slate-400">Configure how other verified adults can reach you regarding this advertisement:</p>
                <div class="space-y-3">
                    <label class="p-3 bg-slate-950 border border-slate-800 rounded-xl flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" wire:model="allow_platform_messages" class="rounded border-slate-700 bg-slate-900 text-rose-600 focus:ring-rose-500">
                        <div>
                            <span class="font-bold text-xs text-white block">Protected Platform Chat Messages</span>
                            <span class="text-[11px] text-slate-400 block">Receive secure, encrypted responses directly inside your LocalAdZone dashboard.</span>
                        </div>
                    </label>

                    <label class="p-3 bg-slate-950 border border-slate-800 rounded-xl flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" wire:model="allow_email_relay" class="rounded border-slate-700 bg-slate-900 text-rose-600 focus:ring-rose-500">
                        <div>
                            <span class="font-bold text-xs text-white block">Anonymous Email Relay</span>
                            <span class="text-[11px] text-slate-400 block">Respondents email an alias relay address without ever seeing your actual personal email.</span>
                        </div>
                    </label>
                </div>

                <div class="pt-2">
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Special Contact Instructions
                    </label>
                    <textarea
                        wire:model="contact_instructions"
                        rows="3"
                        placeholder="e.g. Please send a recent photo and tell me your favorite local coffee spot..."
                        class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-sm text-white focus:ring-2 focus:ring-rose-500 focus:outline-none"
                    ></textarea>
                </div>
            </div>
        @endif

        <!-- Step 10: Complete Preview -->
        @if($currentStep === 10)
            <div class="space-y-5">
                <div class="p-3 bg-blue-950/30 border border-blue-900/40 rounded-xl text-xs text-blue-200">
                    <strong>Listing Preview:</strong> This is how your advertisement will appear to verified local members once approved.
                </div>

                <div class="bg-slate-950 border border-slate-800 rounded-2xl p-6 space-y-4">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span class="text-rose-400 font-semibold">{{ $poster_gender }}, Age {{ $poster_age }}</span>
                        <span class="capitalize">{{ str_replace('_', ' ', $intent) }}</span>
                    </div>

                    <h3 class="text-xl font-bold text-white">{{ $title ?: 'Untitled Listing' }}</h3>

                    <p class="text-xs text-slate-300 leading-relaxed whitespace-pre-line">{{ $description }}</p>

                    @if(!empty($dynamicAttributes))
                        <div class="pt-3 border-t border-slate-800 flex flex-wrap gap-2 text-xs">
                            @foreach($dynamicAttributes as $k => $v)
                                @if(!empty($v))
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-slate-300">
                                        <strong class="text-white capitalize">{{ str_replace('_', ' ', $k) }}:</strong> {{ $v }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($uploadedMedia))
                        <div class="pt-3 border-t border-slate-800 flex gap-2 overflow-x-auto py-2">
                            @foreach($uploadedMedia as $photo)
                                <img src="{{ $photo['thumb'] }}" class="w-20 h-20 object-cover rounded-lg border border-slate-800" alt="Photo">
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Step 11: Policy Confirmation -->
        @if($currentStep === 11)
            <div class="space-y-4">
                <div class="p-4 bg-rose-950/30 border border-rose-900/60 rounded-2xl space-y-3">
                    <h3 class="text-xs font-bold text-rose-400 uppercase tracking-wider">
                        Federal Law & Platform Compliance Affirmation
                    </h3>
                    <p class="text-xs text-rose-200/90 leading-relaxed">
                        LocalAdZone is an adult personal classifieds and dating platform. We operate in strict compliance with federal laws, including 18 U.S.C. § 2421A (FOSTA-SESTA). Commercial sexual services, prostitution, solicitation, sex work, and non-consensual imagery are strictly banned.
                    </p>

                    <div class="space-y-2 pt-2 border-t border-rose-900/40">
                        <label class="flex items-start space-x-2.5 cursor-pointer text-xs text-white">
                            <input type="checkbox" wire:model="confirm_age_18" class="mt-0.5 rounded border-slate-700 bg-slate-950 text-rose-600 focus:ring-rose-500">
                            <span>I certify under penalty of perjury that I am at least 18 years of age.</span>
                        </label>
                        @error('confirm_age_18') <span class="text-xs text-rose-400 block">{{ $message }}</span> @enderror

                        <label class="flex items-start space-x-2.5 cursor-pointer text-xs text-white">
                            <input type="checkbox" wire:model="confirm_community_rules" class="mt-0.5 rounded border-slate-700 bg-slate-950 text-rose-600 focus:ring-rose-500">
                            <span>I agree that this listing is for personal dating, companionship, or friendship only.</span>
                        </label>
                        @error('confirm_community_rules') <span class="text-xs text-rose-400 block">{{ $message }}</span> @enderror

                        <label class="flex items-start space-x-2.5 cursor-pointer text-xs text-white">
                            <input type="checkbox" wire:model="confirm_anti_trafficking" class="mt-0.5 rounded border-slate-700 bg-slate-950 text-rose-600 focus:ring-rose-500">
                            <span>I acknowledge that solicitation of commercial sexual services, prostitution, trafficking, and coercion are strictly forbidden and will result in immediate termination and referral to law enforcement.</span>
                        </label>
                        @error('confirm_anti_trafficking') <span class="text-xs text-rose-400 block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        @endif

        <!-- Step 12: Final Review & Submission -->
        @if($currentStep === 12)
            <div class="space-y-4 text-center py-4">
                <div class="w-12 h-12 bg-rose-500/20 text-rose-400 rounded-2xl flex items-center justify-center mx-auto border border-rose-500/30">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="text-xl font-bold text-white">Ready for Moderation Review</h3>
                <p class="text-xs text-slate-300 max-w-md mx-auto">
                    Your advertisement is ready to be queued for review. Our moderation team inspects submissions to ensure compliance with our zero-tolerance safety policies before listings appear in public search.
                </p>

                <div class="pt-4 flex justify-center space-x-4">
                    <button
                        type="button"
                        wire:click="submitForReview"
                        class="px-6 py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-rose-600/30 flex items-center space-x-2"
                    >
                        <span>Submit for Moderation Review</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </div>
        @endif

        <!-- Navigation Buttons -->
        <div class="pt-6 border-t border-slate-800 flex items-center justify-between">
            <div>
                @if($currentStep > 1)
                    <button
                        type="button"
                        wire:click="previousStep"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition"
                    >
                        &larr; Back
                    </button>
                @endif
            </div>

            <div class="flex items-center space-x-3">
                @if($currentStep < $totalSteps)
                    <button
                        type="button"
                        wire:click="nextStep"
                        class="px-5 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition shadow-md flex items-center space-x-1"
                    >
                        <span>Continue</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
