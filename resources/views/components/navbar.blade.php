@php
    use App\Models\SystemSetting;
    use App\Modules\Categories\Models\Category;
    use App\Modules\Listings\Models\ContactMessage;
    use App\Modules\Locations\Models\City;
    use App\Modules\Locations\Models\State;
    use App\Modules\Notifications\Models\NotificationLog;

    $navBrand = [];
    try {
        $navBrand = SystemSetting::whereIn('key', [
                'site_logo_url',
                'site_mobile_logo_url',
            ])
            ->pluck('value', 'key')
            ->toArray();
    } catch (\Throwable $e) {
    }

    $navLogoUrl = $navBrand['site_logo_url'] ?? '';
    $navMobileLogoUrl = $navBrand['site_mobile_logo_url'] ?? '';
    $hasCustomNavLogo = !empty($navLogoUrl) && !in_array($navLogoUrl, ['/images/logo.png', '/images/logo.svg'], true);
    $hasCustomMobileLogo = !empty($navMobileLogoUrl) && !in_array($navMobileLogoUrl, ['/images/logo-mobile.png', '/images/logo-mobile.svg'], true);

    $navCategories = collect();
    $navStates = collect();
    $navCities = collect();
    try {
        $navCategories = Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $navStates = State::orderBy('name')->get();
        $navCities = City::where('is_popular', true)->with('state')->orderBy('name')->take(24)->get();
    } catch (\Throwable $e) {
    }

    $navUnreadMessages = 0;
    $navRecentMessages = collect();
    $navUnreadNotifs = 0;
    $navRecentNotifs = collect();
    $navIsAdmin = false;

    if (auth()->check()) {
        $navUser = auth()->user();
        $navIsAdmin = method_exists($navUser, 'isAdmin')
            ? $navUser->isAdmin()
            : in_array($navUser->role, ['admin', 'super_admin', 'moderator', 'support_agent'], true);

        try {
            $navUnreadMessages = ContactMessage::where('receiver_id', $navUser->id)
                ->where('is_read', false)
                ->count();
            $navRecentMessages = ContactMessage::where('receiver_id', $navUser->id)
                ->with(['sender', 'listing'])
                ->latest()
                ->take(5)
                ->get();
        } catch (\Throwable $e) {
        }

        try {
            $navUnreadNotifs = NotificationLog::where('user_id', $navUser->id)
                ->whereNull('seen_at')
                ->count();
            $navRecentNotifs = NotificationLog::where('user_id', $navUser->id)
                ->latest()
                ->take(6)
                ->get();
        } catch (\Throwable $e) {
        }
    }
@endphp

<style>
    :root {
        --ldh-pink: #F50057;
        --ldh-pink-hover: #D8004D;
        --ldh-navy: #172033;
        --ldh-bg: #FFFFFF;
        --ldh-bg-secondary: #F8FAFC;
        --ldh-border: #E9EDF3;
        --ldh-muted: #64748B;
        --ldh-dropdown-shadow: 0 20px 45px -12px rgba(23, 32, 51, 0.16), 0 0 0 1px rgba(233, 237, 243, 0.95);
    }

    .ldh-header {
        position: sticky;
        top: 0;
        z-index: 50;
        background-color: var(--ldh-bg);
        border-bottom: 1px solid var(--ldh-border);
        transition: box-shadow 0.2s ease, background-color 0.2s ease;
        width: 100%;
    }

    .ldh-header.is-scrolled {
        background-color: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        box-shadow: 0 8px 28px rgba(23, 32, 51, 0.08);
    }

    .ldh-container {
        max-width: 1440px;
        margin: 0 auto;
        padding: 0 24px;
    }

    .ldh-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 70px;
        gap: 16px;
        flex-wrap: nowrap;
    }

    .ldh-brand {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
        text-decoration: none;
        white-space: nowrap;
        padding: 4px 0;
        border-radius: 12px;
    }

    .ldh-brand-icon {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        background-color: #FFF1F5;
        border: 1px solid #FBCFE8;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--ldh-pink);
        flex-shrink: 0;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .ldh-brand:hover .ldh-brand-icon {
        transform: scale(1.05);
        box-shadow: 0 6px 16px rgba(245, 0, 87, 0.16);
    }

    .ldh-brand-title {
        font-size: 20px;
        font-weight: 900;
        letter-spacing: -0.025em;
        color: var(--ldh-navy);
        line-height: 1.05;
        white-space: nowrap;
    }

    .ldh-brand-title span {
        color: var(--ldh-pink);
    }

    .ldh-brand-tagline {
        font-size: 9.5px;
        font-weight: 600;
        color: var(--ldh-muted);
        letter-spacing: 0.11em;
        text-transform: uppercase;
        margin-top: 3px;
        line-height: 1;
        white-space: nowrap;
    }

    .ldh-desktop-nav {
        display: none;
        align-items: center;
        justify-content: center;
        flex-wrap: nowrap;
        gap: 4px;
        min-width: 0;
    }

    .ldh-nav-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 12px;
        border-radius: 11px;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--ldh-navy);
        background: transparent;
        border: none;
        cursor: pointer;
        white-space: nowrap;
        flex-shrink: 0;
        line-height: 1;
        text-decoration: none;
        transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
    }

    .ldh-nav-item:hover {
        background-color: #FFF1F5;
        color: var(--ldh-pink);
    }

    .ldh-nav-item.is-active-home {
        background-color: var(--ldh-pink);
        color: #FFFFFF;
        box-shadow: 0 4px 12px rgba(245, 0, 87, 0.25);
    }

    .ldh-nav-item.is-active-home:hover {
        background-color: var(--ldh-pink-hover);
        color: #FFFFFF;
    }

    .ldh-nav-item.is-active-soft {
        background-color: #FFF1F5;
        color: var(--ldh-pink);
    }

    .ldh-nav-icon {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
    }

    .ldh-chevron {
        width: 14px;
        height: 14px;
        flex-shrink: 0;
        opacity: 0.65;
        transition: transform 0.15s ease;
    }

    .ldh-desktop-actions {
        display: none;
        align-items: center;
        flex-wrap: nowrap;
        gap: 8px;
        flex-shrink: 0;
    }

    .ldh-icon-btn {
        position: relative;
        width: 38px;
        height: 38px;
        border-radius: 11px;
        border: 1px solid var(--ldh-border);
        background-color: var(--ldh-bg);
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }

    .ldh-icon-btn:hover {
        background-color: #FFF1F5;
        border-color: #FBCFE8;
        color: var(--ldh-pink);
    }

    .ldh-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 9999px;
        background-color: var(--ldh-pink);
        color: #FFFFFF;
        font-size: 10px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #FFFFFF;
        line-height: 1;
    }

    .ldh-profile-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 4px 10px 4px 5px;
        border-radius: 9999px;
        border: 1px solid var(--ldh-border);
        background-color: var(--ldh-bg);
        cursor: pointer;
        flex-shrink: 0;
        white-space: nowrap;
        max-width: 215px;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }

    .ldh-profile-btn:hover {
        border-color: #FBCFE8;
        background-color: var(--ldh-bg-secondary);
    }

    .ldh-avatar {
        width: 30px;
        height: 30px;
        border-radius: 9999px;
        background-color: #FFF1F5;
        border: 1px solid #FBCFE8;
        color: var(--ldh-pink);
        font-size: 12px;
        font-weight: 900;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .ldh-profile-meta {
        display: flex;
        flex-direction: column;
        min-width: 0;
        text-align: left;
        line-height: 1.15;
    }

    .ldh-profile-name {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--ldh-navy);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 132px;
    }

    .ldh-profile-role {
        font-size: 10px;
        font-weight: 700;
        color: var(--ldh-muted);
        white-space: nowrap;
    }

    .ldh-profile-role.is-admin {
        color: var(--ldh-pink);
    }

    .ldh-signin-link {
        display: inline-flex;
        align-items: center;
        padding: 8px 12px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        color: var(--ldh-navy);
        white-space: nowrap;
        flex-shrink: 0;
        text-decoration: none;
        transition: color 0.15s ease, background-color 0.15s ease;
    }

    .ldh-signin-link:hover {
        color: var(--ldh-pink);
        background-color: var(--ldh-bg-secondary);
    }

    .ldh-register-link {
        display: inline-flex;
        align-items: center;
        padding: 8px 13px;
        border-radius: 9999px;
        border: 1px solid #FBCFE8;
        background-color: #FFF1F5;
        color: var(--ldh-pink);
        font-size: 12.5px;
        font-weight: 700;
        white-space: nowrap;
        flex-shrink: 0;
        text-decoration: none;
        transition: background-color 0.15s ease;
    }

    .ldh-register-link:hover {
        background-color: #FFE4EE;
    }

    .ldh-post-ad-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 9px 16px;
        border-radius: 9999px;
        background-color: var(--ldh-pink);
        color: #FFFFFF;
        font-size: 13px;
        font-weight: 800;
        white-space: nowrap;
        flex-shrink: 0;
        text-decoration: none;
        line-height: 1;
        box-shadow: 0 4px 14px rgba(245, 0, 87, 0.28);
        transition: background-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
    }

    .ldh-post-ad-btn:hover {
        background-color: var(--ldh-pink-hover);
        color: #FFFFFF;
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(245, 0, 87, 0.34);
    }

    .ldh-mobile-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .ldh-tablet-post-ad {
        display: none;
    }

    @media (min-width: 768px) and (max-width: 1279px) {
        .ldh-tablet-post-ad {
            display: inline-flex;
        }
    }

    @media (min-width: 1280px) {
        .ldh-desktop-nav {
            display: flex;
        }
        .ldh-desktop-actions {
            display: flex;
        }
        .ldh-mobile-actions {
            display: none;
        }
    }

    @media (min-width: 1280px) and (max-width: 1399px) {
        .ldh-container {
            padding: 0 16px;
        }
        .ldh-bar {
            gap: 10px;
        }
        .ldh-nav-item {
            padding: 8px 9px;
            font-size: 13px;
            gap: 5px;
        }
        .ldh-profile-btn {
            max-width: 185px;
        }
        .ldh-profile-name {
            max-width: 104px;
        }
        .ldh-post-ad-btn {
            padding: 9px 14px;
            font-size: 12.5px;
        }
    }

    @media (min-width: 1600px) {
        .ldh-container {
            max-width: 1520px;
            padding: 0 32px;
        }
        .ldh-desktop-nav {
            gap: 6px;
        }
        .ldh-nav-item {
            padding: 9px 14px;
            font-size: 14px;
        }
    }

    @media (max-width: 479px) {
        .ldh-container {
            padding: 0 14px;
        }
        .ldh-bar {
            height: 64px;
            gap: 8px;
        }
        .ldh-brand-title {
            font-size: 18px;
        }
        .ldh-brand-tagline {
            font-size: 8.5px;
            letter-spacing: 0.08em;
        }
    }
</style>

<header
    x-data="{
        scrolled: false,
        mobileMenuOpen: false,
        activeDropdown: null,
        stateQuery: '',
        cityQuery: '',
        toggle(name) {
            this.activeDropdown = (this.activeDropdown === name) ? null : name;
        },
        closeAll() {
            this.activeDropdown = null;
            this.mobileMenuOpen = false;
        }
    }"
    @scroll.window="scrolled = window.scrollY > 8"
    @keydown.escape.window="closeAll()"
    :class="{ 'is-scrolled': scrolled }"
    class="ldh-header"
>
    <div class="ldh-container">
        <div class="ldh-bar">
            <!-- LEFT SECTION: BRAND LOGO -->
            <a href="{{ route('home') }}" class="ldh-brand">
                @if($hasCustomNavLogo || $hasCustomMobileLogo)
                    @if($hasCustomMobileLogo)
                        <img src="{{ asset($navMobileLogoUrl) }}" alt="LocalDateHub" class="h-9 w-9 object-contain sm:hidden" onerror="this.style.display='none'">
                    @endif
                    <img src="{{ asset($hasCustomNavLogo ? $navLogoUrl : $navMobileLogoUrl) }}" alt="LocalDateHub" class="h-10 max-w-[185px] object-contain {{ $hasCustomMobileLogo ? 'hidden sm:block' : '' }}" onerror="this.style.display='none'">
                @else
                    <div class="ldh-brand-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="ldh-brand-title">LocalDate<span>Hub</span></div>
                        <div class="ldh-brand-tagline">Connect • Meet • Enjoy</div>
                    </div>
                @endif
            </a>

            <!-- CENTER SECTION: SINGLE-LINE DESKTOP NAVIGATION -->
            <nav class="ldh-desktop-nav" aria-label="Main navigation">
                <!-- Home -->
                <a
                    href="{{ route('home') }}"
                    class="ldh-nav-item {{ request()->routeIs('home') ? 'is-active-home' : '' }}"
                >
                    <svg class="ldh-nav-icon" fill="none" stroke="currentColor" stroke-width="2.1" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    <span>Home</span>
                </a>

                <!-- Browse Ads Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'browse') activeDropdown = null">
                    <button
                        type="button"
                        @click="toggle('browse')"
                        :aria-expanded="activeDropdown === 'browse'"
                        aria-haspopup="true"
                        class="ldh-nav-item {{ request()->routeIs('categories.*') ? 'is-active-soft' : '' }}"
                    >
                        <svg class="ldh-nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect width="7" height="7" x="3" y="3" rx="1.5"/>
                            <rect width="7" height="7" x="14" y="3" rx="1.5"/>
                            <rect width="7" height="7" x="14" y="14" rx="1.5"/>
                            <rect width="7" height="7" x="3" y="14" rx="1.5"/>
                        </svg>
                        <span>Browse Ads</span>
                        <svg class="ldh-chevron" :class="{ 'rotate-180': activeDropdown === 'browse' }" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>

                    <div
                        x-show="activeDropdown === 'browse'"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1.5 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                        style="box-shadow: var(--ldh-dropdown-shadow)"
                        class="absolute left-0 mt-2.5 w-64 rounded-2xl bg-white p-2 z-50"
                    >
                        <div class="px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-slate-400">Classified Categories</div>
                        <div class="max-h-72 overflow-y-auto space-y-0.5">
                            @foreach($navCategories as $cat)
                                <a
                                    href="{{ route('categories.show', $cat->slug) }}"
                                    class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:text-[#F50057] hover:bg-rose-50/80 transition"
                                >
                                    <span>{{ $cat->name }}</span>
                                    <span class="text-slate-300">&rarr;</span>
                                </a>
                            @endforeach
                        </div>
                        <div class="border-t border-slate-100 mt-1.5 pt-1.5">
                            <a href="{{ route('categories.index') }}" class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-extrabold text-[#F50057] bg-rose-50/70 hover:bg-rose-100/70 transition">
                                <span>Browse All Categories</span>
                                <span>&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- States Searchable Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'states') activeDropdown = null">
                    <button
                        type="button"
                        @click="toggle('states'); $nextTick(() => { if(activeDropdown === 'states' && $refs.stateSearchInput) $refs.stateSearchInput.focus() })"
                        :aria-expanded="activeDropdown === 'states'"
                        aria-haspopup="true"
                        class="ldh-nav-item {{ request()->routeIs('locations.state') ? 'is-active-soft' : '' }}"
                    >
                        <svg class="ldh-nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/>
                            <line x1="9" x2="9" y1="3" y2="18"/>
                            <line x1="15" x2="15" y1="6" y2="21"/>
                        </svg>
                        <span>States</span>
                        <svg class="ldh-chevron" :class="{ 'rotate-180': activeDropdown === 'states' }" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>

                    <div
                        x-show="activeDropdown === 'states'"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1.5 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                        style="box-shadow: var(--ldh-dropdown-shadow)"
                        class="absolute left-0 mt-2.5 w-80 rounded-2xl bg-white p-3 z-50"
                    >
                        <div class="relative mb-2.5">
                            <input
                                x-ref="stateSearchInput"
                                x-model="stateQuery"
                                type="text"
                                placeholder="Filter states alphabetically..."
                                class="w-full pl-8 pr-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#F50057] focus:bg-white"
                            >
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </div>

                        <div class="max-h-64 overflow-y-auto grid grid-cols-2 gap-1 pr-1">
                            @foreach($navStates as $st)
                                <a
                                    href="{{ route('locations.state', strtolower($st->code)) }}"
                                    x-show="stateQuery.trim() === '' || '{{ strtolower($st->name . ' ' . $st->code) }}'.includes(stateQuery.toLowerCase().trim())"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 hover:text-[#F50057] hover:bg-rose-50/80 transition truncate flex items-center justify-between gap-1"
                                >
                                    <span class="truncate">{{ $st->name }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">{{ $st->code }}</span>
                                </a>
                            @endforeach
                        </div>
                        <div class="border-t border-slate-100 mt-2.5 pt-2 flex items-center justify-between">
                            <span class="text-[10px] font-semibold text-slate-400">{{ $navStates->count() }} US States</span>
                            <a href="{{ route('locations.index') }}" class="text-xs font-extrabold text-[#F50057] hover:underline">All Locations &rarr;</a>
                        </div>
                    </div>
                </div>

                <!-- Cities Searchable Dropdown -->
                <div class="relative" @click.away="if(activeDropdown === 'cities') activeDropdown = null">
                    <button
                        type="button"
                        @click="toggle('cities'); $nextTick(() => { if(activeDropdown === 'cities' && $refs.citySearchInput) $refs.citySearchInput.focus() })"
                        :aria-expanded="activeDropdown === 'cities'"
                        aria-haspopup="true"
                        class="ldh-nav-item {{ request()->routeIs('locations.city') ? 'is-active-soft' : '' }}"
                    >
                        <svg class="ldh-nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span>Cities</span>
                        <svg class="ldh-chevron" :class="{ 'rotate-180': activeDropdown === 'cities' }" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>

                    <div
                        x-show="activeDropdown === 'cities'"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1.5 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                        style="box-shadow: var(--ldh-dropdown-shadow)"
                        class="absolute left-0 mt-2.5 w-72 rounded-2xl bg-white p-3 z-50"
                    >
                        <div class="relative mb-2.5">
                            <input
                                x-ref="citySearchInput"
                                x-model="cityQuery"
                                type="text"
                                placeholder="Search popular cities..."
                                class="w-full pl-8 pr-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#F50057] focus:bg-white"
                            >
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </div>

                        <div class="max-h-64 overflow-y-auto space-y-0.5 pr-1">
                            @foreach($navCities as $ct)
                                <a
                                    href="{{ route('locations.city', ['stateCode' => strtolower($ct->state?->code ?? 'ca'), 'citySlug' => $ct->slug]) }}"
                                    x-show="cityQuery.trim() === '' || '{{ strtolower($ct->name . ' ' . ($ct->state?->code ?? '') . ' ' . ($ct->state?->name ?? '')) }}'.includes(cityQuery.toLowerCase().trim())"
                                    class="flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 hover:text-[#F50057] hover:bg-rose-50/80 transition"
                                >
                                    <span class="truncate">{{ $ct->name }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">{{ $ct->state?->code }}</span>
                                </a>
                            @endforeach
                        </div>
                        <div class="border-t border-slate-100 mt-2.5 pt-2">
                            <a href="{{ route('locations.index') }}" class="flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-extrabold text-[#F50057] bg-rose-50/70 hover:bg-rose-100/70 transition">
                                <span>Explore All Cities &rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- About -->
                <a
                    href="{{ route('pages.about') }}"
                    class="ldh-nav-item {{ request()->routeIs('pages.about') ? 'is-active-soft' : '' }}"
                >
                    <svg class="ldh-nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 16v-4"/>
                        <path d="M12 8h.01"/>
                    </svg>
                    <span>About</span>
                </a>

                <!-- Contact -->
                <a
                    href="{{ route('pages.contact') }}"
                    class="ldh-nav-item {{ request()->routeIs('pages.contact') ? 'is-active-soft' : '' }}"
                >
                    <svg class="ldh-nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect width="20" height="16" x="2" y="4" rx="2"/>
                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                    </svg>
                    <span>Contact</span>
                </a>
            </nav>

            <!-- RIGHT SECTION: DESKTOP ACTION CONTROLS -->
            <div class="ldh-desktop-actions">
                @guest
                    <a href="{{ route('login') }}" class="ldh-signin-link">Sign In</a>
                    <a href="{{ route('register') }}" class="ldh-register-link">Create Account</a>
                @else
                    <!-- Messages Dropdown -->
                    <div class="relative" @click.away="if(activeDropdown === 'messages') activeDropdown = null">
                        <button
                            type="button"
                            @click="toggle('messages')"
                            :aria-expanded="activeDropdown === 'messages'"
                            aria-label="Messages"
                            title="Messages"
                            class="ldh-icon-btn"
                        >
                            <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>
                            </svg>
                            @if($navUnreadMessages > 0)
                                <span class="ldh-badge">{{ $navUnreadMessages > 99 ? '99+' : $navUnreadMessages }}</span>
                            @endif
                        </button>

                        <div
                            x-show="activeDropdown === 'messages'"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1.5 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                            x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                            style="box-shadow: var(--ldh-dropdown-shadow)"
                            class="absolute right-0 mt-2.5 w-80 rounded-2xl bg-white p-3.5 z-50 text-xs"
                        >
                            <div class="flex items-center justify-between pb-2.5 mb-2.5 border-b border-slate-100">
                                <span class="font-black text-slate-900">Messages</span>
                                <a href="{{ route('account.messages.index') }}" class="text-[11px] font-bold text-[#F50057] hover:underline">Open Messenger &rarr;</a>
                            </div>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                @forelse($navRecentMessages as $msg)
                                    <a href="{{ route('account.messages.index') }}" class="block p-2.5 rounded-xl bg-slate-50 hover:bg-rose-50/70 border border-slate-100 transition">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-bold text-slate-900 truncate">{{ $msg->sender?->name ?: ($msg->sender_name ?: 'Member Inquiry') }}</span>
                                            @if(!$msg->is_read)
                                                <span class="w-2 h-2 rounded-full bg-[#F50057] shrink-0"></span>
                                            @endif
                                        </div>
                                        @if($msg->listing)
                                            <div class="text-[10px] font-semibold text-[#F50057] truncate mt-0.5">{{ $msg->listing->title }}</div>
                                        @endif
                                        <div class="text-[10px] text-slate-400 mt-0.5">{{ $msg->created_at?->diffForHumans() }}</div>
                                    </a>
                                @empty
                                    <div class="py-6 text-center text-slate-400 font-semibold">No recent messages.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Dropdown -->
                    <div
                        class="relative"
                        x-data="{
                            unreadNotifs: {{ $navUnreadNotifs }},
                            seenNow: {{ $navUnreadNotifs === 0 ? 'true' : 'false' }},
                            markSeen() {
                                if (this.unreadNotifs > 0) {
                                    this.unreadNotifs = 0;
                                    this.seenNow = true;
                                    fetch('{{ route('notifications.mark-seen') }}', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                        body: JSON.stringify({ scope: 'user' })
                                    });
                                }
                            }
                        }"
                        @click.away="if(activeDropdown === 'notifications') activeDropdown = null"
                    >
                        <button
                            type="button"
                            @click="toggle('notifications'); if (activeDropdown === 'notifications') { markSeen(); }"
                            :aria-expanded="activeDropdown === 'notifications'"
                            aria-label="Notifications"
                            title="Notifications"
                            class="ldh-icon-btn"
                        >
                            <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                            </svg>
                            <span x-show="unreadNotifs > 0" x-cloak x-text="unreadNotifs > 99 ? '99+' : unreadNotifs" class="ldh-badge">
                                {{ $navUnreadNotifs }}
                            </span>
                        </button>

                        <div
                            x-show="activeDropdown === 'notifications'"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1.5 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                            x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                            style="box-shadow: var(--ldh-dropdown-shadow)"
                            class="absolute right-0 mt-2.5 w-80 rounded-2xl bg-white p-3.5 z-50 text-xs"
                        >
                            <div class="flex items-center justify-between pb-2.5 mb-2.5 border-b border-slate-100">
                                <span class="font-black text-slate-900">Notifications</span>
                                <span x-show="seenNow" x-cloak class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">All Caught Up</span>
                            </div>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                @forelse($navRecentNotifs as $nlog)
                                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                        <div class="font-bold text-slate-900">{{ $nlog->subject ?: 'Account Notification' }}</div>
                                        <div class="text-[11px] text-slate-500 line-clamp-2 mt-0.5">{{ \Illuminate\Support\Str::limit(strip_tags((string) ($nlog->metadata['message'] ?? $nlog->type)), 90) }}</div>
                                        <div class="text-[10px] text-slate-400 mt-1">{{ $nlog->created_at?->diffForHumans() }}</div>
                                    </div>
                                @empty
                                    <div class="py-6 text-center text-slate-400 font-semibold">No recent notifications.</div>
                                @endforelse
                            </div>
                            <div class="border-t border-slate-100 mt-2.5 pt-2">
                                <a href="{{ route('account.dashboard', ['tab' => 'notifications']) }}" class="block text-center text-[11px] font-bold text-[#F50057] hover:underline">
                                    View Notification Center &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile Dropdown -->
                    <div class="relative" @click.away="if(activeDropdown === 'account') activeDropdown = null">
                        <button
                            type="button"
                            @click="toggle('account')"
                            :aria-expanded="activeDropdown === 'account'"
                            aria-haspopup="true"
                            class="ldh-profile-btn"
                        >
                            <div class="ldh-avatar">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="ldh-profile-meta">
                                <span class="ldh-profile-name">{{ auth()->user()->name }}</span>
                                <span class="ldh-profile-role {{ $navIsAdmin ? 'is-admin' : '' }}">
                                    {{ $navIsAdmin ? 'Admin' : 'Member' }}
                                </span>
                            </div>
                            <svg class="ldh-chevron" :class="{ 'rotate-180': activeDropdown === 'account' }" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <div
                            x-show="activeDropdown === 'account'"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1.5 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                            x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                            style="box-shadow: var(--ldh-dropdown-shadow)"
                            class="absolute right-0 mt-2.5 w-64 rounded-2xl bg-white py-2 z-50 text-xs"
                        >
                            <div class="px-4 py-2.5 border-b border-slate-100">
                                <div class="font-extrabold text-slate-900 truncate">{{ auth()->user()->name }}</div>
                                <div class="text-[11px] text-slate-400 truncate">{{ auth()->user()->email }}</div>
                            </div>

                            <div class="py-1">
                                <a href="{{ route('account.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-rose-50/70 hover:text-[#F50057] font-semibold transition">
                                    <span>My Dashboard</span>
                                </a>
                                <a href="{{ route('account.dashboard', ['tab' => 'profile']) }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-rose-50/70 hover:text-[#F50057] font-semibold transition">
                                    <span>My Profile</span>
                                </a>
                                <a href="{{ route('account.dashboard', ['tab' => 'listings']) }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-rose-50/70 hover:text-[#F50057] font-semibold transition">
                                    <span>My Advertisements</span>
                                </a>
                                <a href="{{ route('account.dashboard', ['tab' => 'saved']) }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-rose-50/70 hover:text-[#F50057] font-semibold transition">
                                    <span>Saved Ads</span>
                                </a>
                                <a href="{{ route('account.messages.index') }}" class="flex items-center justify-between px-4 py-2 text-slate-700 hover:bg-rose-50/70 hover:text-[#F50057] font-semibold transition">
                                    <span>Messages</span>
                                    @if($navUnreadMessages > 0)
                                        <span class="px-1.5 py-0.5 rounded-full bg-[#F50057] text-white text-[10px] font-bold">{{ $navUnreadMessages }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('account.dashboard', ['tab' => 'security']) }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-rose-50/70 hover:text-[#F50057] font-semibold transition">
                                    <span>Account Settings</span>
                                </a>
                            </div>

                            @if($navIsAdmin)
                                <div class="border-t border-slate-100 py-1 bg-rose-50/40">
                                    <div class="px-4 py-1 text-[10px] font-black uppercase tracking-wider text-[#F50057]">Administration</div>
                                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-800 hover:bg-rose-100/60 hover:text-[#F50057] font-bold transition">
                                        <span>Admin Dashboard</span>
                                    </a>
                                    <a href="{{ route('admin.live-visitors') }}" class="flex items-center gap-2.5 px-4 py-2 text-slate-800 hover:bg-rose-100/60 hover:text-[#F50057] font-bold transition">
                                        <span>Live Visitors Monitor</span>
                                    </a>
                                </div>
                            @endif

                            <div class="border-t border-slate-100 pt-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left flex items-center gap-2.5 px-4 py-2 text-rose-600 hover:bg-rose-50 font-bold transition cursor-pointer">
                                        <span>Sign Out</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endguest

                <!-- Primary CTA: Post Ad -->
                <a href="{{ route('listings.create') }}" class="ldh-post-ad-btn">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" class="shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                    </svg>
                    <span>Post Ad</span>
                </a>
            </div>

            <!-- TABLET & MOBILE RIGHT ACTIONS (< 1280px) -->
            <div class="ldh-mobile-actions">
                @auth
                    <a href="{{ route('account.messages.index') }}" class="ldh-icon-btn" aria-label="Messages">
                        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                        @if($navUnreadMessages > 0)
                            <span class="ldh-badge">{{ $navUnreadMessages > 99 ? '99+' : $navUnreadMessages }}</span>
                        @endif
                    </a>
                    <a href="{{ route('account.dashboard', ['tab' => 'notifications']) }}" class="ldh-icon-btn" aria-label="Notifications">
                        <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path stroke-linecap="round" stroke-linejoin="round" d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                        @if($navUnreadNotifs > 0)
                            <span class="ldh-badge">{{ $navUnreadNotifs > 99 ? '99+' : $navUnreadNotifs }}</span>
                        @endif
                    </a>
                @endauth

                <a href="{{ route('listings.create') }}" class="ldh-post-ad-btn ldh-tablet-post-ad">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" class="shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                    </svg>
                    <span>Post Ad</span>
                </a>

                <button
                    type="button"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    :aria-expanded="mobileMenuOpen"
                    aria-label="Toggle navigation menu"
                    class="ldh-icon-btn"
                >
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile & Tablet Slide-Out Navigation Drawer -->
    <div x-show="mobileMenuOpen" x-cloak class="fixed inset-0 z-50 xl:hidden">
        <div
            x-show="mobileMenuOpen"
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileMenuOpen = false"
            class="fixed inset-0 bg-slate-900/45 backdrop-blur-xs"
        ></div>

        <aside
            x-show="mobileMenuOpen"
            x-transition:enter="transition ease-out duration-200 transform"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed right-0 top-0 bottom-0 w-full max-w-xs bg-white shadow-2xl flex flex-col z-50 overflow-y-auto"
        >
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <div class="font-black text-lg text-slate-900">
                    LocalDate<span class="text-[#F50057]">Hub</span>
                </div>
                <button type="button" @click="mobileMenuOpen = false" class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center cursor-pointer" aria-label="Close menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-4 space-y-4 flex-1">
                <form action="{{ route('search.index') }}" method="GET" class="relative">
                    <input type="text" name="q" placeholder="Search listings, cities..." class="w-full pl-9 pr-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-sm font-medium focus:outline-none focus:border-[#F50057]">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </form>

                <a href="{{ route('listings.create') }}" class="ldh-post-ad-btn w-full py-3">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                    <span>Post Ad</span>
                </a>

                @guest
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('login') }}" class="py-2.5 px-3 rounded-xl border border-slate-200 text-slate-800 font-bold text-xs text-center">Sign In</a>
                        <a href="{{ route('register') }}" class="py-2.5 px-3 rounded-xl bg-rose-50 border border-pink-200 text-[#F50057] font-bold text-xs text-center">Create Account</a>
                    </div>
                @else
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                        <div class="flex items-center gap-2.5">
                            <div class="ldh-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
                            <div class="min-w-0">
                                <div class="text-xs font-extrabold text-slate-900 truncate">{{ auth()->user()->name }}</div>
                                <div class="text-[10px] font-semibold text-[#F50057]">{{ $navIsAdmin ? 'Admin' : 'Member' }}</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-1.5 pt-1 text-xs font-semibold">
                            <a href="{{ route('account.dashboard') }}" class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 text-center">Dashboard</a>
                            <a href="{{ route('account.messages.index') }}" class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 text-center">Messages</a>
                            <a href="{{ route('account.dashboard', ['tab' => 'listings']) }}" class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 text-center">My Ads</a>
                            <a href="{{ route('account.dashboard', ['tab' => 'saved']) }}" class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-700 text-center">Saved</a>
                        </div>
                        @if($navIsAdmin)
                            <div class="grid grid-cols-2 gap-1.5 text-xs font-bold">
                                <a href="{{ route('admin.dashboard') }}" class="px-2.5 py-1.5 rounded-lg bg-rose-50 border border-pink-200 text-[#F50057] text-center">Admin Panel</a>
                                <a href="{{ route('admin.live-visitors') }}" class="px-2.5 py-1.5 rounded-lg bg-rose-50 border border-pink-200 text-[#F50057] text-center">Live Visitors</a>
                            </div>
                        @endif
                    </div>
                @endguest

                <div class="space-y-1 text-sm font-bold text-slate-800">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl {{ request()->routeIs('home') ? 'bg-[#F50057] text-white' : 'hover:bg-rose-50 hover:text-[#F50057]' }}">Home</a>
                    <a href="{{ route('categories.index') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl hover:bg-rose-50 hover:text-[#F50057]">Browse Ads</a>
                    <a href="{{ route('locations.index') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl hover:bg-rose-50 hover:text-[#F50057]">States &amp; Cities</a>
                    <a href="{{ route('pages.about') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl hover:bg-rose-50 hover:text-[#F50057]">About</a>
                    <a href="{{ route('pages.contact') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl hover:bg-rose-50 hover:text-[#F50057]">Contact</a>
                </div>

                @auth
                    <form method="POST" action="{{ route('logout') }}" class="pt-2 border-t border-slate-100">
                        @csrf
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-rose-50 text-rose-600 font-bold text-xs cursor-pointer">Sign Out</button>
                    </form>
                @endauth
            </div>
        </aside>
    </div>
</header>
