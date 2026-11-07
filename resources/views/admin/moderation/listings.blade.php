<x-layouts.app title="Listing Moderation Review Queue — Admin | LocalAdZone">
    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div>
                <div class="flex items-center space-x-2 text-rose-500 font-semibold text-xs tracking-wider uppercase">
                    <a href="{{ route('admin.categories.index') }}" class="hover:text-rose-400">Admin Control</a>
                    <span>/</span>
                    <span>Moderation Queue</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white mt-1">
                    Listing Moderation & Safety Inspection
                </h1>
                <p class="text-slate-400 text-xs sm:text-sm mt-1">
                    Audit pending personal advertisements for FOSTA-SESTA compliance, prohibited commercial terms, and photo safety.
                </p>
            </div>
            <div class="flex items-center space-x-3 text-xs">
                <span class="px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 font-bold">
                    {{ $pendingCount }} Pending Review
                </span>
            </div>
        </div>

        @if(session('status'))
            <div class="p-4 bg-emerald-950/50 border border-emerald-600/40 rounded-xl text-xs text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        @if($listings->isEmpty())
            <div class="bg-slate-900/60 border border-slate-800 rounded-3xl p-12 text-center space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="text-lg font-bold text-white">Moderation Queue Clear</h3>
                <p class="text-xs text-slate-400">There are no pending classified advertisements awaiting review.</p>
            </div>
        @else
            <div class="space-y-6">
                @foreach($listings as $item)
                    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800/80 pb-3 text-xs text-slate-400">
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-white">#{{ $item->id }}</span>
                                <span class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 font-bold text-[10px]">{{ $item->category?->name }}</span>
                                <span>&bull; {{ $item->city?->name }}, {{ $item->state?->code }}</span>
                            </div>
                            <div>
                                <span>Poster: <strong>{{ $item->user?->name }}</strong> (Age: {{ $item->poster_age }}, Gender: {{ $item->poster_gender }})</span>
                                <span class="ml-2">&bull; Submitted {{ $item->created_at->diffForHumans() }}</span>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-bold text-white">{{ $item->title }}</h3>
                            <p class="text-xs text-slate-300 leading-relaxed mt-2 whitespace-pre-line p-3 bg-slate-950 rounded-xl border border-slate-800">
                                {{ $item->description }}
                            </p>
                        </div>

                        <!-- Dynamic Attributes Review -->
                        @if($item->attributes->isNotEmpty())
                            <div class="flex flex-wrap gap-2 text-xs">
                                @foreach($item->attributes as $attr)
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 text-slate-300">
                                        <strong class="text-white capitalize">{{ str_replace('_', ' ', $attr->attribute_code) }}:</strong>
                                        {{ $attr->value_text ?: $attr->value_number }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <!-- Photos Visual Inspection -->
                        @if($item->media->isNotEmpty())
                            <div class="space-y-1.5">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">
                                    Uploaded Photos for Inspection ({{ $item->media->count() }}):
                                </span>
                                <div class="flex gap-3 overflow-x-auto py-2">
                                    @foreach($item->media as $media)
                                        <div class="relative shrink-0">
                                            <img src="{{ asset('storage/' . ($media->thumbnail_path ?: $media->file_path)) }}" class="w-24 h-24 object-cover rounded-xl border border-slate-800" alt="Inspection">
                                            <span class="absolute bottom-1 right-1 text-[9px] px-1 rounded bg-slate-950 text-slate-300 border border-slate-800">
                                                {{ $media->moderation_status }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Moderation Actions -->
                        <div class="pt-4 border-t border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <form action="{{ route('admin.moderation.listings.approve', $item->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition shadow-md flex items-center space-x-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Approve & Publish Ad</span>
                                </button>
                            </form>

                            <form action="{{ route('admin.moderation.listings.reject', $item->id) }}" method="POST" class="flex items-center space-x-2">
                                @csrf
                                <input
                                    type="text"
                                    name="reason"
                                    required
                                    placeholder="Rejection reason for poster..."
                                    class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:ring-1 focus:ring-rose-500 w-64"
                                >
                                <button type="submit" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition shadow">
                                    Reject
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach

                <div class="mt-6">
                    {{ $listings->links() }}
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
