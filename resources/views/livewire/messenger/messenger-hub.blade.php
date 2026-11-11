<div 
    class="messenger-container w-full max-w-[1680px] mx-auto bg-white border border-slate-200/90 rounded-xl sm:rounded-2xl shadow-xl overflow-hidden relative font-sans text-slate-900"
    x-data="{
        lightboxUrl: null,
        scrollToBottom() {
            $nextTick(() => {
                const el = document.getElementById('chat-messages-container');
                if (el) el.scrollTop = el.scrollHeight;
            });
        }
    }"
    x-init="
        scrollToBottom();
        window.addEventListener('scroll-to-bottom', () => scrollToBottom());
    "
>
    <!-- Explicit Layout Styles to guarantee Responsive Mobile & Desktop Layout -->
    <style>
        .messenger-container {
            display: flex !important;
            flex-direction: column !important;
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            overflow: hidden !important;
            position: relative !important;
        }

        /* Mobile View (< 768px): Fit directly in viewport so bottom composer bar is ALWAYS 100% visible */
        @media (max-width: 767.98px) {
            .messenger-container {
                height: calc(100dvh - 110px) !important;
                height: calc(100vh - 110px) !important;
                min-height: 0 !important;
                max-height: calc(100dvh - 100px) !important;
                border-radius: 0.75rem !important;
            }
            .messenger-inbox-col {
                width: 100% !important;
                height: 100% !important;
                min-height: 0 !important;
                flex-direction: column !important;
            }
            .messenger-chat-col {
                width: 100% !important;
                height: 100% !important;
                min-height: 0 !important;
                flex-direction: column !important;
                overflow: hidden !important;
            }
            .messenger-details-col {
                width: 100% !important;
                height: 100% !important;
                min-height: 0 !important;
                flex-direction: column !important;
                overflow-y: auto !important;
            }
        }

        /* Desktop View (>= 768px): 3 Columns side-by-side with independent scrolling */
        @media (min-width: 768px) {
            .messenger-container {
                flex-direction: row !important;
                height: calc(100dvh - 170px) !important;
                height: calc(100vh - 170px) !important;
                min-height: 580px !important;
                max-height: calc(100vh - 140px) !important;
                border-radius: 1rem !important;
            }
            .messenger-inbox-col {
                display: flex !important;
                flex-direction: column !important;
                width: 360px !important;
                min-width: 340px !important;
                max-width: 380px !important;
                height: 100% !important;
                min-height: 0 !important;
                flex-shrink: 0 !important;
                border-right: 1px solid #e2e8f0 !important;
                background-color: #ffffff !important;
            }
            .messenger-chat-col {
                display: flex !important;
                flex-direction: column !important;
                flex: 1 1 0% !important;
                min-width: 0 !important;
                height: 100% !important;
                min-height: 0 !important;
                background-color: #f8fafc !important;
                overflow: hidden !important;
            }
            .messenger-details-col {
                display: flex !important;
                flex-direction: column !important;
                width: 320px !important;
                min-width: 300px !important;
                max-width: 350px !important;
                height: 100% !important;
                min-height: 0 !important;
                flex-shrink: 0 !important;
                border-left: 1px solid #e2e8f0 !important;
                background-color: #ffffff !important;
                overflow-y: auto !important;
            }
        }

        /* Message Timeline & Fixed Docked Composer */
        .messenger-timeline {
            flex: 1 1 0% !important;
            min-height: 0 !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }

        .messenger-composer-bar {
            flex-shrink: 0 !important;
            background-color: #ffffff !important;
            border-top: 1px solid #e2e8f0 !important;
            position: relative !important;
            z-index: 10 !important;
        }
    </style>

    <!-- Toast Notification Overlay -->
    @if(session('messenger_toast'))
        <div 
            x-data="{ show: true }" 
            x-show="show" 
            x-init="setTimeout(() => show = false, 4000)" 
            x-transition 
            class="absolute top-4 right-4 z-50 bg-slate-900 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-xl flex items-center gap-2 border border-slate-700"
        >
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
            <span>{{ session('messenger_toast') }}</span>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- 1. LEFT PANEL: INBOX SIDEBAR (Always on left on desktop)                   -->
    <!-- ========================================================================= -->
    <aside 
        class="messenger-inbox-col {{ $mobileView !== 'inbox' ? 'hidden md:flex' : 'flex' }} z-20"
    >
        <!-- Inbox Top Header -->
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-pink-50 text-[#F20B65] flex items-center justify-center font-black shadow-xs">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.145 2 11.258c0 2.91 1.455 5.518 3.736 7.208V22l3.418-1.874c.91.252 1.872.388 2.846.388 5.523 0 10-4.145 10-9.258C22 6.145 17.523 2 12 2zm1.05 12.443l-2.625-2.8-5.125 2.8 5.637-5.986 2.688 2.8 5.062-2.8-5.637 5.986z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-black text-slate-900 tracking-tight leading-none flex items-center gap-1.5">
                        <span>Messages</span>
                        @php
                            $totalUnread = collect($conversations)->sum('unread_count');
                        @endphp
                        @if($totalUnread > 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-[#F20B65] text-white">
                                {{ $totalUnread }}
                            </span>
                        @endif
                    </h2>
                    <span class="text-[11px] font-semibold text-slate-400">LocalDateHub Messenger</span>
                </div>
            </div>

            <div class="flex items-center gap-1">
                <button 
                    type="button" 
                    wire:click="$toggle('showNewMessageModal')"
                    class="w-8 h-8 rounded-xl bg-slate-50 hover:bg-pink-50 text-slate-600 hover:text-[#F20B65] flex items-center justify-center transition"
                    title="Start New Conversation"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Search Input -->
        <div class="px-4 py-2.5">
            <div class="relative">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input 
                    type="text" 
                    wire:model.live.debounce.250ms="searchQuery"
                    placeholder="Search conversations..." 
                    class="w-full bg-slate-50 border border-slate-200/80 rounded-xl pl-9 pr-8 py-1.5 text-xs font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#F20B65]/20 focus:border-[#F20B65] transition"
                />
                @if($searchQuery)
                    <button wire:click="$set('searchQuery', '')" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 text-xs font-bold">&times;</button>
                @endif
            </div>
        </div>

        <!-- Filter Chips (All, Unread, Buying, Selling, Starred, Archived) -->
        <div class="px-4 pb-2.5 flex items-center gap-1.5 overflow-x-auto scrollbar-none border-b border-slate-100">
            @php
                $filters = [
                    'all' => 'All',
                    'unread' => 'Unread',
                    'buying' => 'Inquiries',
                    'selling' => 'My Ads',
                    'starred' => 'Starred',
                    'archived' => 'Archived',
                ];
            @endphp
            @foreach($filters as $fKey => $fLabel)
                <button 
                    type="button" 
                    wire:click="$set('activeFilter', '{{ $fKey }}')"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold whitespace-nowrap transition {{ $activeFilter === $fKey ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900' }}"
                >
                    {{ $fLabel }}
                </button>
            @endforeach
        </div>

        <!-- Conversation Scrollable List -->
        <div class="flex-1 overflow-y-auto divide-y divide-slate-100/80">
            @forelse($conversations as $conv)
                @php
                    $isActive = ($activeConversationKey === $conv['key']);
                    $hasUnread = ($conv['unread_count'] > 0);
                    $peer = $conv['peer'];
                    $listing = $conv['listing'];
                    $latestMsg = $conv['latest_message'];
                @endphp
                <div 
                    wire:click="selectConversation('{{ $conv['key'] }}')"
                    class="p-3.5 flex items-start gap-3 cursor-pointer transition relative group {{ $isActive ? 'bg-pink-50/70 border-l-4 border-[#F20B65]' : ($hasUnread ? 'bg-slate-50/80 hover:bg-slate-100' : 'hover:bg-slate-50/90') }}"
                >
                    <!-- User Avatar with Status Indicator -->
                    <div class="relative shrink-0">
                        @if($peer?->avatar_url)
                            <img src="{{ $peer->avatar_url }}" alt="{{ $peer->name }}" class="w-11 h-11 rounded-2xl object-cover border border-slate-200" />
                        @else
                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-pink-500 to-rose-600 text-white font-black text-sm flex items-center justify-center shadow-xs">
                                {{ strtoupper(substr($peer?->name ?? 'User', 0, 1)) }}
                            </div>
                        @endif
                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white shadow-xs"></span>
                    </div>

                    <!-- Conversation Text & Meta -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1">
                            <div class="flex items-center gap-1 truncate">
                                <span class="text-xs font-black text-slate-900 truncate {{ $hasUnread ? 'font-black' : 'font-bold' }}">
                                    {{ $peer?->name ?? 'Classified Member' }}
                                </span>
                                @if($peer?->is_identity_verified)
                                    <svg class="w-3.5 h-3.5 text-blue-500 fill-current shrink-0" viewBox="0 0 20 20" title="Verified Member"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                @endif
                            </div>
                            <span class="text-[10px] font-semibold text-slate-400 shrink-0">
                                {{ $latestMsg->created_at->diffForHumans(null, true, true) }}
                            </span>
                        </div>

                        <!-- Listing Context Snippet -->
                        @if($listing)
                            <div class="text-[10px] font-bold text-slate-500 truncate flex items-center gap-1 mt-0.5">
                                <span class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-700 text-[9px] uppercase font-black">Ad</span>
                                <span class="truncate">{{ $listing->title }}</span>
                            </div>
                        @endif

                        <!-- Latest Message Preview -->
                        <div class="flex items-center justify-between gap-2 mt-1">
                            <p class="text-xs truncate {{ $hasUnread ? 'font-black text-slate-900' : 'font-medium text-slate-500' }}">
                                @if($latestMsg->sender_id === Auth::id())
                                    <span class="text-slate-400 font-bold">You: </span>
                                @endif
                                @if($latestMsg->attachment_path)
                                    <span class="text-[#F20B65] font-semibold">📎 {{ $latestMsg->attachment_name ?: 'Attachment' }}</span>
                                @else
                                    {{ $latestMsg->message_encrypted }}
                                @endif
                            </p>

                            <div class="flex items-center gap-1 shrink-0">
                                @if($conv['is_starred'])
                                    <svg class="w-3.5 h-3.5 text-amber-500 fill-amber-500" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                @endif
                                @if($conv['is_pinned'])
                                    <svg class="w-3.5 h-3.5 text-blue-600 fill-blue-600" viewBox="0 0 24 24"><line x1="12" y1="17" x2="12" y2="22"></line><path d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1v4.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24Z"></path></svg>
                                @endif
                                @if($hasUnread)
                                    <span class="w-4 h-4 rounded-full bg-[#F20B65] text-white text-[10px] font-black flex items-center justify-center shadow-xs">
                                        {{ $conv['unread_count'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Associated Listing Mini-Thumb -->
                    @if($listing && $listing->primaryPhoto)
                        <img 
                            src="{{ $listing->primaryPhoto->thumbnail_url }}" 
                            alt="{{ $listing->title }}" 
                            loading="lazy"
                            decoding="async"
                            onerror="this.onerror=null;this.src='{{ asset('images/featured-1.jpg') }}';"
                            class="w-10 h-10 rounded-xl object-contain bg-slate-900 shrink-0 border border-slate-200" 
                        />
                    @endif
                </div>
            @empty
                <div class="p-8 text-center space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-slate-50 text-slate-400 mx-auto flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0012 3c-2.39 0-4.74.175-7.043.513C3.35 3.722 2.25 5.127 2.25 6.748v4.286c0 1.136.847 2.1 1.98 2.193.34.027.68.052 1.02.072v3.091l3-3c.48 0 .956-.008 1.429-.024"/></svg>
                    </div>
                    <p class="text-xs font-bold text-slate-600">No conversations here</p>
                    <p class="text-[11px] text-slate-400">Inquiries on classified listings will appear here in real time.</p>
                </div>
            @endforelse
        </div>
    </aside>

    <!-- ========================================================================= -->
    <!-- 2. CENTER PANEL: ACTIVE CHAT WINDOW (Middle column on desktop)            -->
    <!-- ========================================================================= -->
    <main class="messenger-chat-col {{ $mobileView !== 'chat' ? 'hidden md:flex' : 'flex' }} relative overflow-hidden">
        @if($activePeer)
            <!-- Chat Header -->
            <header class="p-3.5 px-4 bg-white border-b border-slate-200/80 flex items-center justify-between shrink-0 z-10 shadow-xs">
                <div class="flex items-center gap-3">
                    <!-- Mobile Back to Inbox Button -->
                    <button 
                        type="button" 
                        wire:click="backToInbox"
                        class="md:hidden w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition"
                        title="Back to Inbox"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
                    </button>

                    <!-- Peer Avatar -->
                    <div class="relative">
                        @if($activePeer->avatar_url)
                            <img src="{{ $activePeer->avatar_url }}" alt="{{ $activePeer->name }}" class="w-10 h-10 rounded-2xl object-cover border border-slate-200" />
                        @else
                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-pink-500 to-rose-600 text-white font-black text-sm flex items-center justify-center shadow-xs">
                                {{ strtoupper(substr($activePeer->name, 0, 1)) }}
                            </div>
                        @endif
                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white"></span>
                    </div>

                    <!-- Peer Details -->
                    <div>
                        <div class="flex items-center gap-1.5">
                            <h3 class="text-sm font-black text-slate-900 tracking-tight leading-none">
                                {{ $activePeer->name }}
                            </h3>
                            @if($activePeer->is_identity_verified)
                                <svg class="w-3.5 h-3.5 text-blue-500 fill-current" viewBox="0 0 20 20" title="Verified Member"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            @endif
                            @if($this->isStarred)
                                <svg class="w-3.5 h-3.5 text-amber-500 fill-amber-500" viewBox="0 0 24 24" title="Starred"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            @endif
                            @if($this->isPinned)
                                <svg class="w-3.5 h-3.5 text-blue-600 fill-blue-600" viewBox="0 0 24 24" title="Pinned"><line x1="12" y1="17" x2="12" y2="22"></line><path d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1v4.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24Z"></path></svg>
                            @endif
                        </div>
                        <span class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1 mt-0.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                            <span>Active now</span>
                        </span>
                    </div>
                </div>

                <!-- Header Actions (Call icons, Info drawer toggle) -->
                <div class="flex items-center gap-1 sm:gap-2">
                    <!-- Voice Call Button -->
                    <button 
                        type="button" 
                        class="w-8 h-8 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition"
                        title="Voice Call"
                        onclick="alert('Encrypted audio calling is enabled for verified members.')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                    </button>

                    <!-- Video Call Button -->
                    <button 
                        type="button" 
                        class="w-8 h-8 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-600 flex items-center justify-center transition"
                        title="Video Call"
                        onclick="alert('Encrypted video calling is enabled for verified members.')"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polygon points="23 7 16 12 23 17 23 7"></polygon>
                            <rect width="15" height="14" x="1" y="5" rx="2" ry="2"></rect>
                        </svg>
                    </button>

                    <!-- Toggle Conversation Details (Desktop toggle / Mobile view switch) -->
                    <button 
                        type="button" 
                        class="hidden md:flex w-8 h-8 rounded-xl transition items-center justify-center {{ $showRightPanel ? 'bg-pink-50 text-[#F20B65]' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}"
                        wire:click="toggleRightPanel"
                        title="{{ $showRightPanel ? 'Hide Details' : 'Show Details' }}"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    </button>

                    <button 
                        type="button" 
                        class="md:hidden w-8 h-8 rounded-xl bg-slate-50 text-slate-600 hover:bg-slate-100 flex items-center justify-center transition"
                        wire:click="openMobileDetails"
                        title="Conversation Details"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    </button>
                </div>
            </header>

            <!-- Sticky Listing Context Banner -->
            @if($activeListing)
                <div class="bg-white border-b border-slate-200/80 px-3 py-2 sm:px-4 sm:py-2.5 flex items-center justify-between gap-2 sm:gap-3 shrink-0">
                    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                        @if($activeListing->primaryPhoto)
                            <img 
                                src="{{ $activeListing->primaryPhoto->thumbnail_url }}" 
                                alt="{{ $activeListing->title }}" 
                                loading="lazy"
                                decoding="async"
                                onerror="this.onerror=null;this.src='{{ asset('images/featured-1.jpg') }}';"
                                class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl object-contain bg-slate-900 shrink-0 border border-slate-200" 
                            />
                        @else
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 shrink-0 font-black text-xs">
                                Ad
                            </div>
                        @endif

                        <div class="min-w-0">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-wider text-slate-400 block">Classified Listing</span>
                            <a href="{{ route('listings.show', $activeListing->slug) }}" target="_blank" class="text-xs font-bold text-slate-900 hover:text-[#F20B65] transition truncate block">
                                {{ $activeListing->title }}
                            </a>
                            <div class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-[11px] text-slate-500 font-semibold mt-0.5">
                                <span class="text-[#F20B65] font-black">Free Classified</span>
                                <span>&bull;</span>
                                <span class="truncate">{{ $activeListing->city?->name }}, {{ $activeListing->state?->code }}</span>
                            </div>
                        </div>
                    </div>

                    <a 
                        href="{{ route('listings.show', $activeListing->slug) }}" 
                        target="_blank"
                        class="px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-xl bg-slate-50 hover:bg-pink-50 text-[#F20B65] text-[11px] sm:text-xs font-black border border-slate-200 hover:border-pink-200 transition shrink-0 flex items-center gap-1"
                    >
                        <span>View</span>
                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                    </a>
                </div>
            @endif

            <!-- Message Timeline -->
            <div 
                id="chat-messages-container" 
                class="messenger-timeline flex-1 p-3 sm:p-6 space-y-3 sm:space-y-4 scroll-smooth"
            >
                @php
                    $lastDate = null;
                @endphp

                @forelse($activeMessages as $message)
                    @php
                        $isOutgoing = ($message->sender_id === Auth::id());
                        $msgDate = $message->created_at->format('M d, Y');
                    @endphp

                    <!-- Date Separator -->
                    @if($lastDate !== $msgDate)
                        <div class="flex items-center justify-center my-3">
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold bg-slate-200/80 text-slate-600 shadow-2xs">
                                {{ $message->created_at->isToday() ? 'Today' : ($message->created_at->isYesterday() ? 'Yesterday' : $msgDate) }}
                            </span>
                        </div>
                        @php $lastDate = $msgDate; @endphp
                    @endif

                    <!-- Message Row Container -->
                    <div class="flex flex-col {{ $isOutgoing ? 'items-end' : 'items-start' }} group/msg">
                        <!-- Reply Quote Preview if message is replying to another -->
                        @if($message->replyTo)
                            <div class="mb-1 text-[11px] font-semibold text-slate-500 bg-slate-100/80 px-3 py-1 rounded-lg border-l-2 border-[#F20B65] max-w-[80%] truncate">
                                <span class="font-bold text-slate-700">{{ $message->replyTo->sender_id === Auth::id() ? 'You' : $message->replyTo->sender_name }}: </span>
                                <span>{{ $message->replyTo->message_encrypted ?: 'Attachment' }}</span>
                            </div>
                        @endif

                        <!-- Main Bubble & Action Bar Wrapper -->
                        <div class="flex items-end gap-1.5 max-w-[85%] sm:max-w-[75%] {{ $isOutgoing ? 'flex-row-reverse' : 'flex-row' }}">
                            <!-- Bubble -->
                            <div 
                                class="rounded-2xl px-4 py-2.5 text-xs font-normal leading-relaxed break-words shadow-2xs relative {{ $isOutgoing ? 'bg-gradient-to-r from-[#F20B65] to-rose-600 text-white rounded-br-xs' : 'bg-slate-100 text-slate-900 border border-slate-200/80 rounded-bl-xs' }}"
                            >
                                <!-- Attached File/Image Rendering -->
                                @if($message->attachment_path)
                                    <div class="mb-2">
                                        @if($message->attachment_type === 'image')
                                            <img 
                                                src="{{ $message->attachment_url }}" 
                                                alt="{{ $message->attachment_name }}" 
                                                loading="lazy"
                                                decoding="async"
                                                class="rounded-xl max-h-64 max-w-full w-auto object-contain cursor-pointer hover:opacity-95 transition border {{ $isOutgoing ? 'border-pink-400' : 'border-slate-200' }}"
                                                @click="lightboxUrl = '{{ $message->attachment_url }}'"
                                            />
                                        @else
                                            <a 
                                                href="{{ $message->attachment_url }}" 
                                                download="{{ $message->attachment_name }}" 
                                                target="_blank"
                                                class="flex items-center gap-2 p-2.5 rounded-xl transition {{ $isOutgoing ? 'bg-pink-700/60 text-white' : 'bg-white text-slate-800' }}"
                                            >
                                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                                <div class="min-w-0">
                                                    <span class="font-bold truncate block text-[11px]">{{ $message->attachment_name }}</span>
                                                    <span class="text-[9px] opacity-80 block">{{ number_format(($message->attachment_size ?: 1024) / 1024, 1) }} KB</span>
                                                </div>
                                            </a>
                                        @endif
                                    </div>
                                @endif

                                <!-- Message Text -->
                                @if($message->message_encrypted)
                                    <p class="whitespace-pre-wrap">{{ $message->message_encrypted }}</p>
                                @endif

                                <!-- Reactions on bubble -->
                                @if(!empty($message->reactions))
                                    <div class="flex items-center gap-1 mt-1.5 -mb-1 flex-wrap">
                                        @foreach($message->reactions as $emoji => $uids)
                                            <span 
                                                wire:click="toggleReaction({{ $message->id }}, '{{ $emoji }}')"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold cursor-pointer transition {{ in_array(Auth::id(), $uids) ? 'bg-white text-slate-900 border border-slate-300 shadow-xs' : 'bg-black/20 text-white' }}"
                                            >
                                                <span>{{ $emoji }}</span>
                                                <span>{{ count($uids) }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- Hover Action Controls -->
                            <div class="opacity-0 group-hover/msg:opacity-100 transition flex items-center gap-1 text-slate-400">
                                <!-- Modern Floating Quick Reaction Dropdown -->
                                <div class="relative" x-data="{ open: false }">
                                    <button 
                                        type="button" 
                                        @click="open = !open" 
                                        class="w-6 h-6 rounded-full hover:bg-slate-200/80 text-slate-500 hover:text-slate-800 flex items-center justify-center transition"
                                        title="React"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                            <line x1="9" y1="9" x2="9.01" y2="9"></line>
                                            <line x1="15" y1="9" x2="15.01" y2="9"></line>
                                        </svg>
                                    </button>
                                    <div 
                                        x-show="open" 
                                        x-cloak
                                        @click.away="open = false" 
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 translate-y-1 scale-90"
                                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                        class="absolute bottom-7 {{ $isOutgoing ? 'right-0' : 'left-0' }} bg-white border border-slate-200 rounded-full shadow-xl px-2 py-1 flex items-center gap-1 z-40"
                                    >
                                        @foreach(['❤️', '👍', '😂', '😮', '😢', '🔥'] as $e)
                                            <button 
                                                type="button"
                                                wire:click="toggleReaction({{ $message->id }}, '{{ $e }}')" 
                                                @click="open = false" 
                                                class="hover:scale-135 transition-transform p-1 text-sm leading-none"
                                            >
                                                {{ $e }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Reply Action -->
                                <button 
                                    wire:click="setReplyTo({{ $message->id }})" 
                                    class="w-6 h-6 rounded-full hover:bg-slate-200/80 text-slate-500 hover:text-slate-800 flex items-center justify-center transition"
                                    title="Reply"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <polyline points="9 17 4 12 9 7"></polyline>
                                        <path d="M20 18v-2a4 4 0 0 0-4-4H4"></path>
                                    </svg>
                                </button>

                                <!-- Delete own message -->
                                @if($isOutgoing)
                                    <button 
                                        wire:click="deleteMessage({{ $message->id }})" 
                                        wire:confirm="Delete this message?"
                                        class="w-6 h-6 rounded-full hover:bg-rose-100 text-slate-400 hover:text-rose-600 flex items-center justify-center transition"
                                        title="Delete"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path d="M3 6h18"></path>
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Meta: Timestamp & Read Status -->
                        <div class="flex items-center gap-1 text-[10px] font-semibold text-slate-400 mt-1 px-1">
                            <span>{{ $message->created_at->format('h:i A') }}</span>
                            @if($isOutgoing)
                                @if($message->is_read)
                                    <span class="text-blue-500 font-bold" title="Read by {{ $activePeer->name }}">✓✓</span>
                                @else
                                    <span class="text-slate-400 font-bold" title="Delivered">✓</span>
                                @endif
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="h-full flex flex-col items-center justify-center text-center p-8 space-y-3">
                        <div class="w-16 h-16 rounded-3xl bg-pink-50 text-[#F20B65] flex items-center justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
                        </div>
                        <h4 class="text-sm font-black text-slate-900">Start the conversation</h4>
                        <p class="text-xs text-slate-500 max-w-sm">
                            Say hello to {{ $activePeer->name }}! Inquire about their advertisement and arrange a safe connection.
                        </p>
                    </div>
                @endforelse
            </div>

            <!-- Fixed Bottom Message Composer -->
            <div class="messenger-composer-bar p-2.5 sm:p-4 bg-white border-t border-slate-200/80 shrink-0 space-y-2">
                <!-- Reply Quoting Ribbon -->
                @if($replyToMessageId)
                    <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                        <div class="flex items-center gap-2 truncate">
                            <svg class="w-4 h-4 text-[#F20B65] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 016 6v3"/></svg>
                            <span class="text-slate-500 font-semibold truncate">Replying to: <em>"{{ $replyToMessageSnippet }}"</em></span>
                        </div>
                        <button wire:click="clearReply" class="text-slate-400 hover:text-slate-700 font-bold px-2">&times;</button>
                    </div>
                @endif

                <!-- Staged Attachment Preview Ribbon -->
                @if($attachment)
                    <div class="flex items-center justify-between p-2 rounded-xl bg-pink-50 border border-pink-200 text-xs text-[#F20B65]">
                        <div class="flex items-center gap-2 truncate">
                            <span>📎</span>
                            <span class="font-bold truncate">{{ $attachment->getClientOriginalName() }}</span>
                            <span class="text-[10px] text-pink-700">({{ number_format($attachment->getSize() / 1024, 1) }} KB)</span>
                        </div>
                        <button wire:click="removeAttachment" class="text-pink-600 hover:text-pink-900 font-bold px-2">&times;</button>
                    </div>
                @endif

                <!-- Main Composer Input Bar -->
                <form wire:submit.prevent="sendMessage" class="flex items-end gap-1.5 sm:gap-2 relative">
                    <!-- Attachment Trigger -->
                    <label 
                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center cursor-pointer transition shrink-0 border border-slate-200"
                        title="Attach Photo or Document"
                    >
                        <input type="file" wire:model="attachment" class="hidden" accept="image/*,.pdf,.doc,.docx" />
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
                        </svg>
                    </label>

                    <!-- Text Area Input -->
                    <div class="flex-1 relative">
                        <textarea 
                            id="messenger-textarea"
                            wire:model="messageText"
                            rows="1"
                            placeholder="Write a message... (Press Enter to send)"
                            class="w-full bg-slate-50 border border-slate-200/90 rounded-2xl px-3.5 sm:px-4 py-2 sm:py-2.5 text-xs font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#F20B65]/20 focus:border-[#F20B65] resize-none max-h-24 sm:max-h-32 transition scrollbar-none"
                            x-on:keydown.enter.exact.prevent="$wire.sendMessage()"
                        ></textarea>
                    </div>

                    <!-- Modern Responsive Emoji Picker -->
                    <div 
                        class="relative" 
                        x-data="{ 
                            open: false, 
                            category: 'smileys',
                            insertEmoji(emoji) {
                                const textarea = document.getElementById('messenger-textarea');
                                if (textarea) {
                                    const start = textarea.selectionStart || 0;
                                    const end = textarea.selectionEnd || 0;
                                    const text = $wire.messageText || '';
                                    $wire.messageText = text.substring(0, start) + emoji + text.substring(end);
                                    $nextTick(() => {
                                        textarea.focus();
                                        textarea.setSelectionRange(start + emoji.length, start + emoji.length);
                                    });
                                } else {
                                    $wire.messageText = ($wire.messageText || '') + emoji;
                                }
                            }
                        }"
                    >
                        <button 
                            type="button" 
                            @click="open = !open" 
                            class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-500 hover:text-[#F20B65] flex items-center justify-center transition shrink-0 border border-slate-200"
                            :class="{ 'bg-pink-50 text-[#F20B65] border-pink-200': open }"
                            title="Insert Emoji"
                        >
                            <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                                <line x1="9" y1="9" x2="9.01" y2="9"></line>
                                <line x1="15" y1="9" x2="15.01" y2="9"></line>
                            </svg>
                        </button>

                        <!-- Advanced Floating Emoji Popover -->
                        <div 
                            x-show="open" 
                            x-cloak
                            @click.away="open = false" 
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                            class="absolute bottom-12 right-0 w-72 sm:w-80 max-w-[calc(100vw-32px)] bg-white border border-slate-200 rounded-2xl shadow-2xl p-3 z-50 flex flex-col gap-2"
                        >
                            <!-- Category Filter Tabs -->
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <div class="flex items-center gap-1">
                                    <button 
                                        type="button" 
                                        @click="category = 'smileys'"
                                        class="px-2 py-1 rounded-lg text-xs font-bold transition"
                                        :class="category === 'smileys' ? 'bg-[#F20B65] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                                    >
                                        😀 Smileys
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="category = 'love'"
                                        class="px-2 py-1 rounded-lg text-xs font-bold transition"
                                        :class="category === 'love' ? 'bg-[#F20B65] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                                    >
                                        ❤️ Hearts
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="category = 'gestures'"
                                        class="px-2 py-1 rounded-lg text-xs font-bold transition"
                                        :class="category === 'gestures' ? 'bg-[#F20B65] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                                    >
                                        👍 Hands
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="category = 'fun'"
                                        class="px-2 py-1 rounded-lg text-xs font-bold transition"
                                        :class="category === 'fun' ? 'bg-[#F20B65] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100'"
                                    >
                                        🎉 Fun
                                    </button>
                                </div>
                                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold px-1">&times;</button>
                            </div>

                            <!-- Emoji Scrollable Grid -->
                            <div class="max-h-48 overflow-y-auto pr-1">
                                <!-- Smileys -->
                                <div x-show="category === 'smileys'" class="grid grid-cols-7 gap-1 text-center">
                                    @foreach(['😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','😉','😌','😍','🥰','😘','😋','😛','😜','🤪','😎','😏','🤩','🥳','🥺','🤫','🤗','🤔','😴','😷','🥵','🥶','🤯','🥳','😂','🔥','✨'] as $emoji)
                                        <button 
                                            type="button" 
                                            @click="insertEmoji('{{ $emoji }}')"
                                            class="w-8 h-8 rounded-lg hover:bg-slate-100 text-lg flex items-center justify-center hover:scale-125 transition-transform"
                                        >
                                            {{ $emoji }}
                                        </button>
                                    @endforeach
                                </div>

                                <!-- Love & Hearts -->
                                <div x-show="category === 'love'" class="grid grid-cols-7 gap-1 text-center" style="display: none;">
                                    @foreach(['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟','💌','💋','🔥','✨','⭐','🌟','💫','⚡','💐'] as $emoji)
                                        <button 
                                            type="button" 
                                            @click="insertEmoji('{{ $emoji }}')"
                                            class="w-8 h-8 rounded-lg hover:bg-slate-100 text-lg flex items-center justify-center hover:scale-125 transition-transform"
                                        >
                                            {{ $emoji }}
                                        </button>
                                    @endforeach
                                </div>

                                <!-- Gestures -->
                                <div x-show="category === 'gestures'" class="grid grid-cols-7 gap-1 text-center" style="display: none;">
                                    @foreach(['👍','👎','👏','🙌','🤝','🙏','✌️','🤞','👋','👌','💪','🤙','👊','🖐️','💅','👀','🗣️','💋','👑','💍'] as $emoji)
                                        <button 
                                            type="button" 
                                            @click="insertEmoji('{{ $emoji }}')"
                                            class="w-8 h-8 rounded-lg hover:bg-slate-100 text-lg flex items-center justify-center hover:scale-125 transition-transform"
                                        >
                                            {{ $emoji }}
                                        </button>
                                    @endforeach
                                </div>

                                <!-- Fun & Romance -->
                                <div x-show="category === 'fun'" class="grid grid-cols-7 gap-1 text-center" style="display: none;">
                                    @foreach(['🌹','💐','🌸','☕','🍷','🍸','🥂','🍻','🎉','🎈','🎁','🍫','🍓','🍰','🚗','✈️','🏖️','🌙','☀️','🎶'] as $emoji)
                                        <button 
                                            type="button" 
                                            @click="insertEmoji('{{ $emoji }}')"
                                            class="w-8 h-8 rounded-lg hover:bg-slate-100 text-lg flex items-center justify-center hover:scale-125 transition-transform"
                                        >
                                            {{ $emoji }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Send Button -->
                    <button 
                        type="submit" 
                        class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-r from-[#F20B65] to-rose-600 hover:from-[#d90858] hover:to-rose-700 text-white flex items-center justify-center transition shrink-0 shadow-md cursor-pointer disabled:opacity-50"
                        title="Send Message"
                    >
                        <svg class="w-4 h-4 fill-current rotate-45 -mr-0.5" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </button>
                </form>
            </div>
        @else
            <!-- No Conversation Selected Empty State -->
            <div class="h-full flex flex-col items-center justify-center p-8 text-center space-y-4">
                <div class="w-20 h-20 rounded-3xl bg-pink-50 text-[#F20B65] flex items-center justify-center shadow-xs">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-black text-slate-900 tracking-tight">Your Messages</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm">
                        Select a conversation from the sidebar to view active messages, or start a new inquiry on any verified classified listing.
                    </p>
                </div>
                <button 
                    type="button" 
                    wire:click="$toggle('showNewMessageModal')"
                    class="px-4 py-2 rounded-xl bg-[#F20B65] text-white text-xs font-black shadow-md transition hover:bg-[#d90858]"
                    style="background-color: #F20B65 !important; color: #ffffff !important;"
                >
                    New Message
                </button>
            </div>
        @endif
    </main>

    <!-- ========================================================================= -->
    <!-- 3. RIGHT PANEL: CONVERSATION DETAILS & OPTIONS (Right column on desktop)  -->
    <!-- ========================================================================= -->
    @if($activePeer && ($showRightPanel || $mobileView === 'details'))
        <aside 
            class="messenger-details-col {{ $mobileView !== 'details' ? 'hidden md:flex' : 'flex' }} z-10"
        >
            <!-- Details Header -->
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <!-- Mobile Back to Chat Button -->
                    <button 
                        type="button" 
                        wire:click="backToChat"
                        class="md:hidden w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition"
                        title="Back to Chat"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-500">Conversation Details</h3>
                </div>

                <!-- Desktop Close Button -->
                <button 
                    type="button"
                    wire:click="toggleRightPanel" 
                    class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center transition"
                    title="Close Details Panel"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <!-- Section A: Contact Information -->
            <div class="p-6 text-center border-b border-slate-100 flex flex-col items-center">
                <div class="relative mb-3">
                    @if($activePeer->avatar_url)
                        <img src="{{ $activePeer->avatar_url }}" alt="{{ $activePeer->name }}" class="w-20 h-20 rounded-3xl object-cover border-2 border-slate-200 shadow-md" />
                    @else
                        <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-pink-500 to-rose-600 text-white font-black text-2xl flex items-center justify-center shadow-md">
                            {{ strtoupper(substr($activePeer->name, 0, 1)) }}
                        </div>
                    @endif
                    <span class="absolute bottom-0 right-0 w-4 h-4 rounded-full bg-emerald-500 border-2 border-white shadow-xs"></span>
                </div>

                <div class="flex items-center gap-1.5 justify-center">
                    <h4 class="text-base font-black text-slate-900 tracking-tight">{{ $activePeer->name }}</h4>
                    @if($activePeer->is_identity_verified)
                        <svg class="w-4 h-4 text-blue-500 fill-current" viewBox="0 0 20 20" title="Verified Member"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    @endif
                </div>
                <span class="text-xs font-semibold text-slate-400">@ {{ $activePeer->username ?? 'member' }}</span>
                <span class="text-[11px] font-medium text-slate-500 mt-1">Member since {{ $activePeer->created_at->format('M Y') }}</span>

                <!-- Block & Report Actions -->
                <div class="flex items-center gap-2 mt-4">
                    <button 
                        type="button"
                        wire:click="$set('reportModal', true)" 
                        class="px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200 transition flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                            <line x1="4" y1="22" x2="4" y2="15"></line>
                        </svg>
                        <span>Report</span>
                    </button>
                    @if($this->isUserBlocked)
                        <button 
                            type="button"
                            wire:click="unblockUser" 
                            class="px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold border border-emerald-200 transition flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                <path d="m9 12 2 2 4-4"></path>
                            </svg>
                            <span>Unblock</span>
                        </button>
                    @else
                        <button 
                            type="button"
                            wire:click="$set('blockConfirmModal', true)" 
                            class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold border border-rose-200 transition flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="m4.9 4.9 14.2 14.2"></path>
                            </svg>
                            <span>Block</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Section B: Listing Details Summary -->
            @if($activeListing)
                <div class="p-4 border-b border-slate-100 space-y-2.5">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Active Advertisement Context</span>
                    <div class="rounded-xl border border-slate-200 p-2.5 bg-slate-50 flex items-center gap-2.5">
                        @if($activeListing->primaryPhoto)
                            <img src="{{ $activeListing->primaryPhoto->thumbnail_url }}" alt="{{ $activeListing->title }}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='{{ asset('images/featured-1.jpg') }}';" class="w-12 h-12 rounded-lg object-contain bg-slate-900 shrink-0" />
                        @endif
                        <div class="min-w-0 flex-1">
                            <span class="text-xs font-bold text-slate-900 truncate block">{{ $activeListing->title }}</span>
                            <span class="text-[11px] text-slate-500">{{ $activeListing->city?->name }}, {{ $activeListing->state?->code }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Section C: Conversation Options (Fully Functional with Lucide SVGs) -->
            <div class="p-4 border-b border-slate-100 space-y-1.5">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block mb-2">Options</span>
                
                <!-- Star Conversation Toggle -->
                <button 
                    type="button"
                    wire:click="toggleStarConversation" 
                    class="w-full px-3 py-2.5 rounded-xl transition flex items-center justify-between text-xs font-bold {{ $this->isStarred ? 'bg-amber-50 text-amber-800 border border-amber-200/80 shadow-2xs' : 'hover:bg-slate-50 text-slate-700' }}"
                >
                    <span class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 {{ $this->isStarred ? 'text-amber-500 fill-amber-500' : 'text-slate-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                        </svg>
                        <span>{{ $this->isStarred ? 'Starred Conversation' : 'Star Conversation' }}</span>
                    </span>
                    @if($this->isStarred)
                        <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-md bg-amber-200/80 text-amber-900">Starred</span>
                    @else
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                    @endif
                </button>

                <!-- Pin to Top Toggle -->
                <button 
                    type="button"
                    wire:click="togglePinConversation" 
                    class="w-full px-3 py-2.5 rounded-xl transition flex items-center justify-between text-xs font-bold {{ $this->isPinned ? 'bg-blue-50 text-blue-800 border border-blue-200/80 shadow-2xs' : 'hover:bg-slate-50 text-slate-700' }}"
                >
                    <span class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 {{ $this->isPinned ? 'text-blue-600 fill-blue-600' : 'text-slate-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="17" x2="12" y2="22"></line>
                            <path d="M5 17h14v-1.76a2 2 0 0 0-1.11-1.79l-1.78-.9A2 2 0 0 1 15 10.76V6h1a2 2 0 0 0 0-4H8a2 2 0 0 0 0 4h1v4.76a2 2 0 0 1-1.11 1.79l-1.78.9A2 2 0 0 0 5 15.24Z"></path>
                        </svg>
                        <span>{{ $this->isPinned ? 'Pinned to Top' : 'Pin to Top' }}</span>
                    </span>
                    @if($this->isPinned)
                        <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-md bg-blue-200/80 text-blue-900">Pinned</span>
                    @else
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                    @endif
                </button>

                <!-- Archive Thread Toggle -->
                <button 
                    type="button"
                    wire:click="toggleArchiveConversation" 
                    class="w-full px-3 py-2.5 rounded-xl transition flex items-center justify-between text-xs font-bold {{ $this->isArchived ? 'bg-purple-50 text-purple-800 border border-purple-200/80 shadow-2xs' : 'hover:bg-slate-50 text-slate-700' }}"
                >
                    <span class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 {{ $this->isArchived ? 'text-purple-600 fill-purple-600/30' : 'text-slate-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect width="20" height="5" x="2" y="3" rx="1"></rect>
                            <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"></path>
                            <path d="M10 12h4"></path>
                        </svg>
                        <span>{{ $this->isArchived ? 'Archived Thread' : 'Archive Thread' }}</span>
                    </span>
                    @if($this->isArchived)
                        <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-md bg-purple-200/80 text-purple-900">Archived</span>
                    @else
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                    @endif
                </button>

                <!-- Delete Conversation -->
                <button 
                    type="button"
                    wire:click="$set('deleteConfirmModal', true)" 
                    class="w-full px-3 py-2.5 rounded-xl hover:bg-rose-50 flex items-center justify-between text-xs font-bold text-rose-600 transition"
                >
                    <span class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M3 6h18"></path>
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                        </svg>
                        <span>Delete Conversation</span>
                    </span>
                    <svg class="w-3.5 h-3.5 text-rose-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>

            <!-- Section D: Shared Media Gallery -->
            <div class="p-4 flex-1 flex flex-col space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Shared Media</span>
                    <div class="flex items-center gap-1">
                        <button 
                            type="button"
                            wire:click="$set('mediaTab', 'photos')" 
                            class="px-2 py-0.5 rounded text-[10px] font-bold {{ $mediaTab === 'photos' ? 'bg-[#F20B65] text-white' : 'text-slate-600 hover:bg-slate-100' }}"
                        >
                            Photos
                        </button>
                        <button 
                            type="button"
                            wire:click="$set('mediaTab', 'files')" 
                            class="px-2 py-0.5 rounded text-[10px] font-bold {{ $mediaTab === 'files' ? 'bg-[#F20B65] text-white' : 'text-slate-600 hover:bg-slate-100' }}"
                        >
                            Files
                        </button>
                    </div>
                </div>

                @if(!empty($sharedMedia))
                    @if($mediaTab === 'photos')
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($sharedMedia as $mediaItem)
                                <img 
                                    src="{{ $mediaItem->attachment_url }}" 
                                    alt="{{ $mediaItem->attachment_name }}" 
                                    class="w-full aspect-square rounded-xl object-cover cursor-pointer hover:opacity-90 transition border border-slate-200" 
                                    @click="lightboxUrl = '{{ $mediaItem->attachment_url }}'"
                                />
                            @endforeach
                        </div>
                    @else
                        <div class="space-y-1.5">
                            @foreach($sharedMedia as $fileItem)
                                <a 
                                    href="{{ $fileItem->attachment_url }}" 
                                    download="{{ $fileItem->attachment_name }}" 
                                    class="p-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 flex items-center gap-2 text-xs transition"
                                >
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                    <div class="min-w-0 flex-1">
                                        <span class="font-bold truncate block text-[11px] text-slate-800">{{ $fileItem->attachment_name }}</span>
                                        <span class="text-[9px] text-slate-400">{{ number_format(($fileItem->attachment_size ?: 1024)/1024, 1) }} KB</span>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                </a>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="p-6 text-center border border-dashed border-slate-200 rounded-2xl text-slate-400 text-xs">
                        No shared {{ $mediaTab }} yet in this chat.
                    </div>
                @endif
            </div>
        </aside>
    @endif

    <!-- ========================================================================= -->
    <!-- 4. MODALS: LIGHTBOX, NEW MESSAGE, BLOCK, REPORT, DELETE                   -->
    <!-- ========================================================================= -->

    <!-- Lightbox Modal for Photo Previews -->
    <div 
        x-show="lightboxUrl" 
        x-transition.opacity 
        class="fixed inset-0 z-50 bg-slate-950/90 backdrop-blur-md flex items-center justify-center p-4"
        style="display: none;"
    >
        <button 
            @click="lightboxUrl = null" 
            class="absolute top-4 right-4 text-white hover:text-rose-400 font-black text-2xl p-2"
        >
            &times;
        </button>
        <img :src="lightboxUrl" alt="Enlarged photo" class="max-h-[90vh] max-w-[90vw] rounded-2xl shadow-2xl object-contain" />
    </div>

    <!-- New Message Compose Modal -->
    @if($showNewMessageModal)
        <div class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-black text-slate-900 tracking-tight">New Conversation</h3>
                    <button wire:click="$set('showNewMessageModal', false)" class="text-slate-400 hover:text-slate-700 text-lg font-bold">&times;</button>
                </div>

                <div class="space-y-3">
                    <!-- Recipient Selection -->
                    <div>
                        <label class="text-[11px] font-bold text-slate-600 block mb-1">Select Member</label>
                        @php
                            $availableUsers = \App\Models\User::where('id', '!=', Auth::id())->take(8)->get();
                        @endphp
                        <select wire:model="selectedRecipientIdForNew" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#F20B65]/20">
                            <option value="">-- Choose Member --</option>
                            @foreach($availableUsers as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->username ?? $u->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Optional Listing Reference -->
                    <div>
                        <label class="text-[11px] font-bold text-slate-600 block mb-1">Attach to Classified Listing (Optional)</label>
                        @php
                            $availableAds = \App\Modules\Listings\Models\Listing::where('status', 'active')->latest()->take(10)->get();
                        @endphp
                        <select wire:model="selectedListingIdForNew" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-[#F20B65]/20">
                            <option value="">-- Direct Chat (No Listing) --</option>
                            @foreach($availableAds as $ad)
                                <option value="{{ $ad->id }}">{{ $ad->title }} ({{ $ad->city?->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2">
                        <button wire:click="$set('showNewMessageModal', false)" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200">
                            Cancel
                        </button>
                        <button 
                            type="button" 
                            wire:click="
                                @if($selectedRecipientIdForNew)
                                    selectConversation('{{ $selectedRecipientIdForNew }}_{{ $selectedListingIdForNew ?: 0 }}');
                                    $set('showNewMessageModal', false);
                                @endif
                            "
                            class="px-4 py-2 rounded-xl bg-[#F20B65] text-white font-bold text-xs hover:bg-[#d90858]"
                            style="background-color: #F20B65 !important; color: #ffffff !important;"
                        >
                            Open Chat
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Block User Confirmation Modal -->
    @if($blockConfirmModal)
        <div class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-sm p-6 space-y-4 text-center">
                <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 mx-auto flex items-center justify-center font-black text-xl">
                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">Block {{ $activePeer?->name }}?</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        They will no longer be able to message you or view your active classified advertisements.
                    </p>
                </div>
                <div class="flex items-center justify-center gap-2 pt-2">
                    <button wire:click="$set('blockConfirmModal', false)" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200">
                        Cancel
                    </button>
                    <button wire:click="confirmBlockUser" class="px-4 py-2 rounded-xl bg-rose-600 text-white font-bold text-xs hover:bg-rose-700">
                        Yes, Block User
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Report User Modal -->
    @if($reportModal)
        <div class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-md p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-black text-slate-900 tracking-tight">Report Violation</h3>
                    <button wire:click="$set('reportModal', false)" class="text-slate-400 hover:text-slate-700 font-bold">&times;</button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="text-[11px] font-bold text-slate-600 block mb-1">Violation Type</label>
                        <select wire:model="reportReason" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-900">
                            <option value="spam">Spam / Unsolicited Promotion</option>
                            <option value="harassment">Harassment / Abusive Behavior</option>
                            <option value="commercial_solicitation">Commercial Solicitation</option>
                            <option value="fraud_scam">Fraud / Scam / Fake Profile</option>
                            <option value="non_consensual">Inappropriate / Non-consensual Content</option>
                            <option value="other">Other Violation</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-slate-600 block mb-1">Details (Optional)</label>
                        <textarea wire:model="reportNotes" rows="3" placeholder="Explain what happened..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-xs text-slate-900 resize-none"></textarea>
                    </div>
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button wire:click="$set('reportModal', false)" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200">
                            Cancel
                        </button>
                        <button wire:click="submitReport" class="px-4 py-2 rounded-xl bg-[#F20B65] text-white font-bold text-xs hover:bg-[#d90858]" style="background-color: #F20B65 !important; color: #ffffff !important;">
                            Submit Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Conversation Modal -->
    @if($deleteConfirmModal)
        <div class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-sm p-6 space-y-4 text-center">
                <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 mx-auto flex items-center justify-center font-black text-xl">
                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M3 6h18"></path>
                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">Delete Conversation?</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        This conversation with {{ $activePeer?->name }} will be permanently removed from your inbox.
                    </p>
                </div>
                <div class="flex items-center justify-center gap-2 pt-2">
                    <button wire:click="$set('deleteConfirmModal', false)" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200">
                        Cancel
                    </button>
                    <button wire:click="deleteConversation" class="px-4 py-2 rounded-xl bg-rose-600 text-white font-bold text-xs hover:bg-rose-700">
                        Yes, Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
