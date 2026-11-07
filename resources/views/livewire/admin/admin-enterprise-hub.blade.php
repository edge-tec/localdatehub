<div class="flex flex-col lg:flex-row gap-6">
    {{-- ================= Sidebar Navigation ================= --}}
    <aside class="lg:w-60 shrink-0" aria-label="Administrative modules">
        <nav class="bg-slate-900 border border-slate-800 rounded-2xl p-3 lg:sticky lg:top-20 max-h-[80vh] overflow-y-auto">
            @foreach($tabs->groupBy(fn ($meta) => $meta[2], true) as $group => $groupTabs)
                <div class="mb-3">
                    <div class="px-2 pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-500">{{ $group }}</div>
                    @foreach($groupTabs as $key => $meta)
                        <button type="button" wire:click="setTab('{{ $key }}')"
                            class="w-full text-left px-3 py-1.5 rounded-lg text-sm transition {{ $tab === $key ? 'bg-rose-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800' }}">
                            {{ $meta[0] }}
                        </button>
                    @endforeach
                </div>
            @endforeach
        </nav>
    </aside>

    {{-- ================= Main Panel ================= --}}
    <section class="flex-1 min-w-0">
        @if(session()->has('success'))
            <div class="mb-4 rounded-xl border border-emerald-700 bg-emerald-950/60 px-4 py-3 text-sm text-emerald-200" role="status">{{ session('success') }}</div>
        @endif
        @if(session()->has('error'))
            <div class="mb-4 rounded-xl border border-red-700 bg-red-950/60 px-4 py-3 text-sm text-red-200" role="alert">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-red-700 bg-red-950/60 px-4 py-3 text-sm text-red-200" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-black text-white">{{ \App\Livewire\Admin\AdminEnterpriseHub::TABS[$tab][0] ?? 'Dashboard' }}</h1>
            <span class="text-xs text-slate-400">Role: <span class="font-semibold text-rose-300">{{ str_replace('_', ' ', $currentRole) }}</span></span>
        </div>

        @php
            $card = 'bg-slate-900 border border-slate-800 rounded-2xl';
            $th = 'px-3 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400';
            $td = 'px-3 py-2 text-sm text-slate-200 align-top';
            $btn = 'inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold transition';
            $input = 'w-full rounded-lg bg-slate-950 border border-slate-700 text-sm text-slate-100 px-3 py-2 focus:border-rose-500 focus:ring-rose-500';
            $badge = fn ($s) => match ($s) {
                'active', 'approved', 'completed', 'resolved', 'granted', 'published', true => 'bg-emerald-900/60 text-emerald-300 border-emerald-700',
                'pending', 'pending_review', 'open', 'investigating', 'under_review', 'past_due' => 'bg-amber-900/60 text-amber-300 border-amber-700',
                'suspended', 'rejected', 'failed', 'refunded', 'canceled', 'dismissed', false => 'bg-red-900/60 text-red-300 border-red-700',
                default => 'bg-slate-800 text-slate-300 border-slate-600',
            };
        @endphp

        @if(!$tabAllowed)
            <div class="{{ $card }} p-8 text-center text-slate-300">Access denied for this module.</div>
        @else

        {{-- ================= 0. DASHBOARD ================= --}}
        @if($tab === 'dashboard')
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                @foreach([
                    ['Total Accounts', number_format($metrics['totalUsers'])],
                    ['Active Accounts', number_format($metrics['activeUsers'])],
                    ['Suspended Accounts', number_format($metrics['suspendedUsers'])],
                    ['Pending Listings', number_format($metrics['pendingListings'])],
                    ['Published Listings', number_format($metrics['publishedListings'])],
                    ['Rejected Listings', number_format($metrics['rejectedListings'])],
                    ['Expired Listings', number_format($metrics['expiredListings'])],
                    ['Open Reports', number_format($metrics['openReports']) . ' / ' . number_format($metrics['totalReports'])],
                    ['Moderation Queue', number_format($metrics['moderationQueue'])],
                    ['Pending Age Checks', number_format($metrics['pendingVerifications'])],
                    ['Pending Appeals', number_format($metrics['pendingAppeals'])],
                    ['Total Revenue', '$' . number_format($metrics['totalRevenue'], 2)],
                    ['Listing Views', number_format($metrics['totalViews'])],
                ] as [$label, $value])
                    <div class="{{ $card }} p-4">
                        <div class="text-[11px] uppercase tracking-wider text-slate-400">{{ $label }}</div>
                        <div class="mt-1 text-2xl font-black text-white">{{ $value }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div class="{{ $card }} p-5">
                    <h2 class="font-bold text-white mb-3">System Health</h2>
                    <ul class="space-y-2 text-sm">
                        <li class="flex justify-between"><span>Database</span><span class="font-semibold {{ $health['database'] ? 'text-emerald-400' : 'text-red-400' }}">{{ $health['database'] ? 'Operational' : 'Unavailable' }}</span></li>
                        <li class="flex justify-between"><span>Cache</span><span class="font-semibold {{ $health['cache'] ? 'text-emerald-400' : 'text-red-400' }}">{{ $health['cache'] ? 'Operational' : 'Unavailable' }}</span></li>
                        <li class="flex justify-between"><span>Queue driver</span><span class="text-slate-300">{{ $health['queue'] }}</span></li>
                        <li class="flex justify-between"><span>PHP / Laravel</span><span class="text-slate-300">{{ $health['php'] }} / {{ $health['laravel'] }}</span></li>
                        <li class="flex justify-between"><span>Environment</span><span class="text-slate-300">{{ $health['environment'] }}</span></li>
                    </ul>
                </div>
                <div class="{{ $card }} p-5">
                    <h2 class="font-bold text-white mb-3">Recent Administrative Activity</h2>
                    @forelse($recentAudit as $log)
                        <div class="flex justify-between text-sm border-b border-slate-800 py-1.5">
                            <span><span class="font-mono text-rose-300">{{ $log->action }}</span> by {{ $log->admin?->name ?? 'System' }}</span>
                            <span class="text-slate-500">{{ $log->created_at?->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">No recent activity{{ $this->userCan('audit.view') ? '' : ' visible to your role' }}.</p>
                    @endforelse
                </div>
            </div>
        @endif

        {{-- ================= 17. ANALYTICS ================= --}}
        @if($tab === 'analytics')
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                @foreach([
                    ['Views (30d)', number_format($analytics['views30'])],
                    ['Contact Clicks (30d)', number_format($analytics['contacts30'])],
                    ['Favorites (30d)', number_format($analytics['favorites30'])],
                    ['New Users (30d)', number_format($analytics['newUsers30'])],
                    ['New Listings (30d)', number_format($analytics['newListings30'])],
                    ['Revenue (30d)', '$' . number_format($analytics['revenue30'], 2)],
                    ['Refunds (30d)', '$' . number_format($analytics['refunds30'], 2)],
                ] as [$label, $value])
                    <div class="{{ $card }} p-4">
                        <div class="text-[11px] uppercase tracking-wider text-slate-400">{{ $label }}</div>
                        <div class="mt-1 text-xl font-black text-white">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
            <div class="grid md:grid-cols-3 gap-4">
                <div class="{{ $card }} p-5 md:col-span-2 overflow-x-auto">
                    <h2 class="font-bold text-white mb-3">Top Listings by Views</h2>
                    <table class="min-w-full"><thead><tr><th class="{{ $th }}">Title</th><th class="{{ $th }}">Views</th><th class="{{ $th }}">Replies</th><th class="{{ $th }}">Favs</th></tr></thead>
                        <tbody>@forelse($topListings as $l)<tr class="border-t border-slate-800"><td class="{{ $td }}">{{ $l->title }}</td><td class="{{ $td }}">{{ number_format($l->view_count) }}</td><td class="{{ $td }}">{{ $l->reply_count }}</td><td class="{{ $td }}">{{ $l->favorite_count }}</td></tr>@empty<tr><td class="{{ $td }}" colspan="4">No listings yet.</td></tr>@endforelse</tbody></table>
                </div>
                <div class="{{ $card }} p-5">
                    <h2 class="font-bold text-white mb-3">Listings by Status</h2>
                    @forelse($listingsByStatus as $status => $total)
                        <div class="flex justify-between text-sm py-1"><span>{{ str_replace('_', ' ', $status) }}</span><span class="font-semibold">{{ $total }}</span></div>
                    @empty <p class="text-sm text-slate-400">No data.</p> @endforelse
                    <h2 class="font-bold text-white mt-5 mb-3">Top Categories</h2>
                    @foreach($topCategories as $c)
                        <div class="flex justify-between text-sm py-1"><span>{{ $c->name }}</span><span class="font-semibold">{{ $c->listings_count }}</span></div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ================= 1. USERS ================= --}}
        @if($tab === 'users')
            <div class="{{ $card }} p-4 mb-4 grid md:grid-cols-3 gap-3">
                <input type="search" wire:model.live.debounce.400ms="userSearch" placeholder="Search name or email…" class="{{ $input }}" aria-label="Search users">
                <select wire:model.live="userStatusFilter" class="{{ $input }}" aria-label="Status filter">
                    <option value="all">All statuses</option><option value="active">Active</option><option value="suspended">Suspended</option><option value="pending_verification">Pending verification</option><option value="closed">Closed</option>
                </select>
                <select wire:model.live="userRoleFilter" class="{{ $input }}" aria-label="Role filter">
                    <option value="all">All roles</option>
                    @foreach($assignableRoles as $slug => $name)<option value="{{ $slug }}">{{ $name }}</option>@endforeach
                </select>
            </div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">User</th><th class="{{ $th }}">Role</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">18+</th><th class="{{ $th }}">Joined</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($users as $u)
                        <tr class="border-t border-slate-800" wire:key="user-{{ $u->id }}">
                            <td class="{{ $td }}"><div class="font-semibold">{{ $u->name }}</div><div class="text-xs text-slate-400">{{ $u->email }}</div></td>
                            <td class="{{ $td }}">{{ str_replace('_', ' ', $u->role) }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($u->account_status) }}">{{ $u->account_status }}</span></td>
                            <td class="{{ $td }}">{{ $u->is_age_verified ? '✔' : '—' }}</td>
                            <td class="{{ $td }}">{{ $u->created_at?->format('M j, Y') }}</td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if($this->userCan('users.suspend') && $u->id !== auth()->id())
                                    @if($u->account_status === 'suspended')
                                        <button wire:click="unsuspendUser({{ $u->id }})" wire:confirm="Reinstate this account?" class="{{ $btn }} bg-emerald-700 hover:bg-emerald-600 text-white">Reinstate</button>
                                    @else
                                        <button wire:click="openSuspendUserModal({{ $u->id }})" class="{{ $btn }} bg-red-700 hover:bg-red-600 text-white">Suspend</button>
                                    @endif
                                @endif
                                @if($this->userCan('staff.manage') && $u->id !== auth()->id())
                                    <button wire:click="openAssignRoleModal({{ $u->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Role</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No users match the filters.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $users->links() }}</div>
            </div>
        @endif

        {{-- ================= 2. LISTINGS ================= --}}
        @if($tab === 'listings')
            <div class="{{ $card }} p-4 mb-4 grid md:grid-cols-3 gap-3">
                <input type="search" wire:model.live.debounce.400ms="listingSearch" placeholder="Search title…" class="{{ $input }}" aria-label="Search listings">
                <select wire:model.live="listingStatusFilter" class="{{ $input }}" aria-label="Listing status">
                    <option value="all">All statuses</option>
                    @foreach(['draft','pending_review','active','paused','expired','rejected','suspended','archived'] as $s)<option value="{{ $s }}">{{ str_replace('_', ' ', $s) }}</option>@endforeach
                </select>
                <select wire:model.live="listingCategoryFilter" class="{{ $input }}" aria-label="Category">
                    <option value="">All categories</option>
                    @foreach($categoryOptions as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div class="flex gap-2 mb-3">
                @if($this->userCan('listings.approve'))<button wire:click="bulkApproveListings" class="{{ $btn }} bg-emerald-700 hover:bg-emerald-600 text-white">Approve selected ({{ count($selectedListingIds) }})</button>@endif
                @if($this->userCan('listings.suspend'))<button wire:click="bulkSuspendListings" wire:confirm="Suspend all selected listings?" class="{{ $btn }} bg-red-700 hover:bg-red-600 text-white">Suspend selected</button>@endif
            </div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}"><span class="sr-only">Select</span></th><th class="{{ $th }}">Listing</th><th class="{{ $th }}">Category / City</th><th class="{{ $th }}">Owner</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($listings as $l)
                        <tr class="border-t border-slate-800" wire:key="listing-{{ $l->id }}">
                            <td class="{{ $td }}"><input type="checkbox" value="{{ $l->id }}" wire:model.live="selectedListingIds" class="rounded border-slate-600 bg-slate-950 text-rose-600" aria-label="Select listing {{ $l->id }}"></td>
                            <td class="{{ $td }}"><a href="{{ route('listings.show', $l->slug) }}" target="_blank" class="font-semibold hover:text-rose-300">{{ $l->title }}</a><div class="text-xs text-slate-500">#{{ $l->id }} · {{ $l->created_at?->format('M j, Y') }}</div></td>
                            <td class="{{ $td }}">{{ $l->category?->name }}<div class="text-xs text-slate-400">{{ $l->city?->name }}</div></td>
                            <td class="{{ $td }}">{{ $l->user?->name }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($l->status) }}">{{ str_replace('_', ' ', $l->status) }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if($this->userCan('listings.approve') && $l->status !== 'active')<button wire:click="approveListing({{ $l->id }})" class="{{ $btn }} bg-emerald-700 hover:bg-emerald-600 text-white">Approve</button>@endif
                                @if($this->userCan('listings.reject') && $l->status !== 'rejected')<button wire:click="openRejectListingModal({{ $l->id }})" class="{{ $btn }} bg-amber-700 hover:bg-amber-600 text-white">Reject</button>@endif
                                @if($this->userCan('listings.suspend') && $l->status !== 'suspended')<button wire:click="suspendListing({{ $l->id }})" wire:confirm="Suspend this listing?" class="{{ $btn }} bg-red-700 hover:bg-red-600 text-white">Suspend</button>@endif
                                @if($this->userCan('listings.delete'))<button wire:click="deleteListing({{ $l->id }})" wire:confirm="Move this listing to trash?" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Delete</button>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No listings found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $listings->links() }}</div>
            </div>
        @endif

        {{-- ================= 6. MODERATION ================= --}}
        @if($tab === 'moderation')
            <div class="{{ $card }} overflow-x-auto mb-6">
                <div class="p-4 flex justify-between items-center">
                    <h2 class="font-bold text-white">Pending Review Queue (oldest first)</h2>
                    <a href="{{ route('admin.moderation.messages.queue') }}" class="text-xs text-rose-300 hover:underline">Message safety queue &rarr;</a>
                </div>
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Listing</th><th class="{{ $th }}">Owner</th><th class="{{ $th }}">Submitted</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($pendingQueue as $l)
                        <tr class="border-t border-slate-800" wire:key="queue-{{ $l->id }}">
                            <td class="{{ $td }}"><div class="font-semibold">{{ $l->title }}</div><div class="text-xs text-slate-400 line-clamp-2">{{ \Illuminate\Support\Str::limit($l->description, 160) }}</div></td>
                            <td class="{{ $td }}">{{ $l->user?->name }}</td>
                            <td class="{{ $td }}">{{ $l->created_at?->diffForHumans() }}</td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if($this->userCan('listings.approve'))<button wire:click="approveListing({{ $l->id }})" class="{{ $btn }} bg-emerald-700 hover:bg-emerald-600 text-white">Approve</button>@endif
                                @if($this->userCan('listings.reject'))<button wire:click="openRejectListingModal({{ $l->id }})" class="{{ $btn }} bg-amber-700 hover:bg-amber-600 text-white">Reject</button>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="{{ $td }} text-center text-slate-400">The review queue is empty. 🎉</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $pendingQueue->links() }}</div>
            </div>

            <div class="{{ $card }} overflow-x-auto">
                <h2 class="font-bold text-white p-4">Suspension Appeals</h2>
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">User</th><th class="{{ $th }}">Appeal</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($appeals as $a)
                        <tr class="border-t border-slate-800" wire:key="appeal-{{ $a->id }}">
                            <td class="{{ $td }}">{{ $a->user?->name }}</td>
                            <td class="{{ $td }}">{{ \Illuminate\Support\Str::limit($a->appeal_reason, 200) }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($a->status) }}">{{ $a->status }}</span></td>
                            <td class="{{ $td }}">
                                @if($this->userCan('moderation.handle_appeals') && in_array($a->status, ['pending', 'under_review']))
                                    <button wire:click="openAppealModal({{ $a->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Review</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="{{ $td }} text-center text-slate-400">No appeals.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 7. VERIFICATION ================= --}}
        @if($tab === 'verification')
            <div class="{{ $card }} p-4 mb-4 md:w-1/3">
                <select wire:model.live="verificationStatusFilter" class="{{ $input }}" aria-label="Verification status">
                    <option value="pending">Pending</option><option value="approved">Approved</option><option value="rejected">Rejected</option><option value="all">All</option>
                </select>
            </div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">User</th><th class="{{ $th }}">Method</th><th class="{{ $th }}">Declared DOB</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Submitted</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($verifications as $v)
                        <tr class="border-t border-slate-800" wire:key="ver-{{ $v->id }}">
                            <td class="{{ $td }}">{{ $v->user?->name }}<div class="text-xs text-slate-400">{{ $v->user?->email }}</div></td>
                            <td class="{{ $td }}">{{ str_replace('_', ' ', $v->method) }}</td>
                            <td class="{{ $td }}">{{ $v->declared_birthdate?->format('Y-m-d') ?? '—' }} @if($v->declared_birthdate)<span class="text-xs text-slate-400">({{ $v->declared_birthdate->age }} yrs)</span>@endif</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($v->status) }}">{{ $v->status }}</span></td>
                            <td class="{{ $td }}">{{ $v->created_at?->diffForHumans() }}</td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if($v->status === 'pending')
                                    <button wire:click="approveAgeVerification({{ $v->id }})" wire:confirm="Confirm this person is 18 or older?" class="{{ $btn }} bg-emerald-700 hover:bg-emerald-600 text-white">Approve 18+</button>
                                    <button wire:click="rejectAgeVerification({{ $v->id }})" class="{{ $btn }} bg-red-700 hover:bg-red-600 text-white">Reject</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No verification requests.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $verifications->links() }}</div>
            </div>
        @endif

        {{-- ================= 8. REPORTS ================= --}}
        @if($tab === 'reports')
            <div class="{{ $card }} p-4 mb-4 md:w-1/3">
                <select wire:model.live="reportStatusFilter" class="{{ $input }}" aria-label="Report status">
                    <option value="open">Open</option><option value="investigating">Investigating</option><option value="resolved">Resolved</option><option value="dismissed">Dismissed</option><option value="all">All</option>
                </select>
            </div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">#</th><th class="{{ $th }}">Reason</th><th class="{{ $th }}">Target</th><th class="{{ $th }}">Details</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($reports as $r)
                        <tr class="border-t border-slate-800" wire:key="report-{{ $r->id }}">
                            <td class="{{ $td }}">{{ $r->id }}</td>
                            <td class="{{ $td }}"><span class="font-semibold {{ in_array($r->reason_code, ['minor_exploitation', 'commercial_solicitation', 'non_consensual']) ? 'text-red-300' : '' }}">{{ str_replace('_', ' ', $r->reason_code) }}</span></td>
                            <td class="{{ $td }}">{{ class_basename($r->reportable_type) }} #{{ $r->reportable_id }}</td>
                            <td class="{{ $td }}">{{ \Illuminate\Support\Str::limit($r->details, 140) }}<div class="text-xs text-slate-500">by {{ $r->reporter?->name ?? 'Anonymous' }} · {{ $r->created_at?->diffForHumans() }}</div></td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($r->status) }}">{{ $r->status }}</span></td>
                            <td class="{{ $td }}">
                                @if(in_array($r->status, ['open', 'investigating']))
                                    <button wire:click="openResolveReportModal({{ $r->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Action</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No reports.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $reports->links() }}</div>
            </div>
        @endif

        {{-- ================= 9. CONTACT ABUSE ================= --}}
        @if($tab === 'abuse')
            <div class="{{ $card }} overflow-x-auto">
                <div class="p-4 flex justify-between"><h2 class="font-bold text-white">Flagged Contact Messages</h2><a href="{{ route('admin.moderation.messages.queue') }}" class="text-xs text-rose-300 hover:underline">Full message moderation tools &rarr;</a></div>
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">#</th><th class="{{ $th }}">Listing</th><th class="{{ $th }}">Recipient</th><th class="{{ $th }}">Received</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($abuseMessages as $m)
                        <tr class="border-t border-slate-800" wire:key="abuse-{{ $m->id }}">
                            <td class="{{ $td }}">{{ $m->id }}</td>
                            <td class="{{ $td }}">{{ $m->listing?->title ?? '—' }}</td>
                            <td class="{{ $td }}">{{ $m->recipient?->name ?? '—' }}</td>
                            <td class="{{ $td }}">{{ $m->created_at?->diffForHumans() }}</td>
                            <td class="{{ $td }}">@if($this->userCan('moderation.act'))<button wire:click="dismissFlaggedMessage({{ $m->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Dismiss flag</button>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="{{ $td }} text-center text-slate-400">No flagged messages.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $abuseMessages->links() }}</div>
            </div>
        @endif

        {{-- ================= 3. CATEGORIES ================= --}}
        @if($tab === 'categories')
            <div class="flex justify-end mb-3"><a href="{{ route('admin.categories.create') }}" class="{{ $btn }} bg-rose-600 hover:bg-rose-500 text-white">+ New Category</a></div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Category</th><th class="{{ $th }}">Slug</th><th class="{{ $th }}">Listings</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @foreach($categories as $c)
                        <tr class="border-t border-slate-800" wire:key="cat-{{ $c->id }}">
                            <td class="{{ $td }} {{ $c->parent_id ? 'pl-8' : 'font-semibold' }}">{{ $c->name }}</td>
                            <td class="{{ $td }} font-mono text-xs">{{ $c->slug }}</td>
                            <td class="{{ $td }}">{{ $c->listings_count }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge((bool) $c->is_active) }}">{{ $c->is_active ? 'active' : 'inactive' }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                <button wire:click="toggleCategory({{ $c->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">{{ $c->is_active ? 'Deactivate' : 'Activate' }}</button>
                                <a href="{{ route('admin.categories.edit', $c) }}" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Edit</a>
                                <a href="{{ route('admin.categories.attributes', $c) }}" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Attributes</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 5. ATTRIBUTES ================= --}}
        @if($tab === 'attributes')
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Attribute</th><th class="{{ $th }}">Category</th><th class="{{ $th }}">Options</th><th class="{{ $th }}">Required</th><th class="{{ $th }}">Filterable</th><th class="{{ $th }}">Manage</th></tr></thead>
                    <tbody>
                    @forelse($attributes as $a)
                        <tr class="border-t border-slate-800" wire:key="attr-{{ $a->id }}">
                            <td class="{{ $td }} font-semibold">{{ $a->name }}</td>
                            <td class="{{ $td }}">{{ $a->category?->name ?? 'Global' }}</td>
                            <td class="{{ $td }}">{{ $a->options_count }}</td>
                            <td class="{{ $td }}">{{ $a->is_required ? 'Yes' : 'No' }}</td>
                            <td class="{{ $td }}">{{ $a->is_filterable ? 'Yes' : 'No' }}</td>
                            <td class="{{ $td }}">@if($a->category)<a href="{{ route('admin.categories.attributes', $a->category) }}" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Manage</a>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No attributes defined.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 4. LOCATIONS ================= --}}
        @if($tab === 'locations')
            <div class="flex justify-between items-center mb-3">
                <p class="text-sm text-slate-400">{{ $states->count() }} states/territories · {{ number_format($cityCount) }} cities</p>
                <a href="{{ route('admin.locations.index') }}" class="{{ $btn }} bg-rose-600 hover:bg-rose-500 text-white">City, alias &amp; ZIP manager &rarr;</a>
            </div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">State</th><th class="{{ $th }}">Code</th><th class="{{ $th }}">Cities</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @foreach($states as $s)
                        <tr class="border-t border-slate-800" wire:key="state-{{ $s->id }}">
                            <td class="{{ $td }}">{{ $s->name }}</td>
                            <td class="{{ $td }} font-mono">{{ $s->code }}</td>
                            <td class="{{ $td }}">{{ $s->cities_count }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge((bool) $s->is_active) }}">{{ $s->is_active ? 'active' : 'inactive' }}</span></td>
                            <td class="{{ $td }}"><button wire:click="toggleState({{ $s->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">{{ $s->is_active ? 'Deactivate' : 'Activate' }}</button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 10. PRICING ================= --}}
        @if($tab === 'pricing')
            @if($this->userCan('billing.manage_pricing'))
                <div class="flex justify-end mb-3"><button wire:click="openPlanModal" class="{{ $btn }} bg-rose-600 hover:bg-rose-500 text-white">+ New Plan</button></div>
            @endif
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Plan</th><th class="{{ $th }}">Type</th><th class="{{ $th }}">Price</th><th class="{{ $th }}">Duration</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($pricingPlans as $p)
                        <tr class="border-t border-slate-800" wire:key="plan-{{ $p->id }}">
                            <td class="{{ $td }}"><div class="font-semibold">{{ $p->name }}</div><div class="text-xs text-slate-500 font-mono">{{ $p->slug }}</div></td>
                            <td class="{{ $td }}">{{ str_replace('_', ' ', $p->plan_type) }}</td>
                            <td class="{{ $td }}">${{ number_format($p->price_cents / 100, 2) }}</td>
                            <td class="{{ $td }}">{{ $p->duration_days }} days</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge((bool) $p->is_active) }}">{{ $p->is_active ? 'active' : 'inactive' }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if($this->userCan('billing.manage_pricing'))
                                    <button wire:click="openPlanModal({{ $p->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Edit</button>
                                    <button wire:click="togglePlan({{ $p->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">{{ $p->is_active ? 'Disable' : 'Enable' }}</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No pricing plans configured.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 11. FEATURED ================= --}}
        @if($tab === 'featured')
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Listing</th><th class="{{ $th }}">Owner</th><th class="{{ $th }}">Placement</th><th class="{{ $th }}">Window</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($featuredListings as $f)
                        <tr class="border-t border-slate-800" wire:key="feat-{{ $f->id }}">
                            <td class="{{ $td }}">{{ $f->listing?->title ?? 'Deleted listing' }}</td>
                            <td class="{{ $td }}">{{ $f->user?->name }}</td>
                            <td class="{{ $td }}">{{ str_replace('_', ' ', $f->placement) }}</td>
                            <td class="{{ $td }} text-xs">{{ $f->starts_at?->format('M j') }} → {{ $f->ends_at?->format('M j, Y') }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($f->is_active && $f->ends_at?->isFuture()) }}">{{ $f->is_active && $f->ends_at?->isFuture() ? 'active' : 'ended' }}</span></td>
                            <td class="{{ $td }}">@if($f->is_active && $this->userCan('billing.manage_pricing'))<button wire:click="deactivateFeatured({{ $f->id }})" wire:confirm="End this featured placement now?" class="{{ $btn }} bg-red-700 hover:bg-red-600 text-white">Deactivate</button>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No featured placements.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $featuredListings->links() }}</div>
            </div>
        @endif

        {{-- ================= 12. SUBSCRIPTIONS ================= --}}
        @if($tab === 'subscriptions')
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">User</th><th class="{{ $th }}">Plan</th><th class="{{ $th }}">Gateway</th><th class="{{ $th }}">Period</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($subscriptions as $s)
                        <tr class="border-t border-slate-800" wire:key="sub-{{ $s->id }}">
                            <td class="{{ $td }}">{{ $s->user?->name }}</td>
                            <td class="{{ $td }}">{{ $s->plan?->name }}</td>
                            <td class="{{ $td }}">{{ $s->gateway }}</td>
                            <td class="{{ $td }} text-xs">{{ $s->starts_at?->format('M j, Y') }} → {{ $s->ends_at?->format('M j, Y') ?? 'open' }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($s->status) }}">{{ $s->status }}</span></td>
                            <td class="{{ $td }}">@if($s->status === 'active' && $this->userCan('billing.manage_pricing'))<button wire:click="cancelSubscription({{ $s->id }})" wire:confirm="Cancel this subscription?" class="{{ $btn }} bg-red-700 hover:bg-red-600 text-white">Cancel</button>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No subscriptions.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $subscriptions->links() }}</div>
            </div>
        @endif

        {{-- ================= 13. PAYMENTS & REFUNDS ================= --}}
        @if($tab === 'payments')
            <div class="{{ $card }} p-4 mb-4 md:w-1/3">
                <select wire:model.live="paymentStatusFilter" class="{{ $input }}" aria-label="Payment status">
                    <option value="all">All</option><option value="completed">Completed</option><option value="pending">Pending</option><option value="failed">Failed</option><option value="refunded">Refunded</option>
                </select>
            </div>
            <div class="{{ $card }} overflow-x-auto mb-6">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Reference</th><th class="{{ $th }}">User</th><th class="{{ $th }}">Plan</th><th class="{{ $th }}">Amount</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Date</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($transactions as $t)
                        <tr class="border-t border-slate-800" wire:key="txn-{{ $t->id }}">
                            <td class="{{ $td }} font-mono text-xs">{{ $t->reference_id }}</td>
                            <td class="{{ $td }}">{{ $t->user?->name }}</td>
                            <td class="{{ $td }}">{{ $t->plan?->name ?? '—' }}</td>
                            <td class="{{ $td }}">${{ number_format($t->amount_cents / 100, 2) }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($t->status) }}">{{ $t->status }}</span></td>
                            <td class="{{ $td }}">{{ $t->created_at?->format('M j, Y') }}</td>
                            <td class="{{ $td }}">@if($t->status === 'completed' && $this->userCan('billing.refunds'))<button wire:click="openRefundModal({{ $t->id }})" class="{{ $btn }} bg-amber-700 hover:bg-amber-600 text-white">Refund</button>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="{{ $td }} text-center text-slate-400">No transactions.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $transactions->links() }}</div>
            </div>
            <div class="{{ $card }} p-5">
                <h2 class="font-bold text-white mb-3">Recent Refunds</h2>
                @forelse($refunds as $rf)
                    <div class="flex justify-between text-sm border-b border-slate-800 py-1.5">
                        <span>${{ number_format($rf->amount_cents / 100, 2) }} · {{ $rf->transaction?->reference_id }} · {{ $rf->reason }}</span>
                        <span class="text-slate-500">{{ $rf->processedBy?->name }} · {{ $rf->created_at?->diffForHumans() }}</span>
                    </div>
                @empty <p class="text-sm text-slate-400">No refunds issued.</p> @endforelse
            </div>
        @endif

        {{-- ================= 16. ADVERTISEMENTS ================= --}}
        @if($tab === 'ads')
            <div class="flex justify-end mb-3"><button wire:click="openAdModal" class="{{ $btn }} bg-rose-600 hover:bg-rose-500 text-white">+ New Placement</button></div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Name</th><th class="{{ $th }}">Slot</th><th class="{{ $th }}">Advertiser</th><th class="{{ $th }}">Window</th><th class="{{ $th }}">Impr / Clicks / CTR</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($ads as $ad)
                        <tr class="border-t border-slate-800" wire:key="ad-{{ $ad->id }}">
                            <td class="{{ $td }} font-semibold">{{ $ad->name }}</td>
                            <td class="{{ $td }}">{{ str_replace('_', ' ', $ad->slot) }}</td>
                            <td class="{{ $td }}">{{ $ad->advertiser_name ?? '—' }}</td>
                            <td class="{{ $td }} text-xs">{{ $ad->starts_at?->format('M j, Y') ?? 'now' }} → {{ $ad->ends_at?->format('M j, Y') ?? 'open' }}</td>
                            <td class="{{ $td }}">{{ number_format($ad->impressions) }} / {{ number_format($ad->clicks) }} / {{ $ad->clickThroughRate() }}%</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge((bool) $ad->is_active) }}">{{ $ad->is_active ? 'active' : 'paused' }}</span></td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                <button wire:click="openAdModal({{ $ad->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Edit</button>
                                <button wire:click="toggleAd({{ $ad->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">{{ $ad->is_active ? 'Pause' : 'Activate' }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="{{ $td }} text-center text-slate-400">No advertisement placements yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 14. SEO ================= --}}
        @if($tab === 'seo')
            <div class="flex justify-end mb-3"><button wire:click="openSeoModal" class="{{ $btn }} bg-rose-600 hover:bg-rose-500 text-white">+ New SEO Entry</button></div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Route / Path</th><th class="{{ $th }}">Meta Title</th><th class="{{ $th }}">Robots</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($seoMetas as $m)
                        <tr class="border-t border-slate-800" wire:key="seo-{{ $m->id }}">
                            <td class="{{ $td }} font-mono text-xs">{{ $m->page_route ?? class_basename($m->model_type) . ' #' . $m->model_id }}</td>
                            <td class="{{ $td }}">{{ $m->meta_title }}<div class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($m->meta_description, 100) }}</div></td>
                            <td class="{{ $td }}">{{ $m->robots_directive }}</td>
                            <td class="{{ $td }}"><button wire:click="openSeoModal({{ $m->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Edit</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="{{ $td }} text-center text-slate-400">No SEO overrides configured.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 15. CMS ================= --}}
        @if($tab === 'cms')
            <div class="flex justify-end mb-3"><button wire:click="openCmsModal" class="{{ $btn }} bg-rose-600 hover:bg-rose-500 text-white">+ New Page</button></div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Title</th><th class="{{ $th }}">Slug</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Updated</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($cmsPages as $p)
                        <tr class="border-t border-slate-800" wire:key="cms-{{ $p->id }}">
                            <td class="{{ $td }} font-semibold">{{ $p->title }}</td>
                            <td class="{{ $td }} font-mono text-xs">{{ $p->slug }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge((bool) $p->is_published) }}">{{ $p->is_published ? 'published' : 'draft' }}</span></td>
                            <td class="{{ $td }}">{{ $p->updated_at?->diffForHumans() }}</td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                <button wire:click="openCmsModal({{ $p->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Edit</button>
                                <button wire:click="toggleCmsPublish({{ $p->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">{{ $p->is_published ? 'Unpublish' : 'Publish' }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="{{ $td }} text-center text-slate-400">No CMS pages yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 18. STAFF ================= --}}
        @if($tab === 'staff')
            <p class="text-sm text-slate-400 mb-3">To promote a member, open the <button wire:click="setTab('users')" class="text-rose-300 underline">Users</button> module and choose “Role”.</p>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">Staff Member</th><th class="{{ $th }}">Role</th><th class="{{ $th }}">Status</th><th class="{{ $th }}">Last Login</th><th class="{{ $th }}">Actions</th></tr></thead>
                    <tbody>
                    @forelse($staffMembers as $s)
                        <tr class="border-t border-slate-800" wire:key="staff-{{ $s->id }}">
                            <td class="{{ $td }}">{{ $s->name }}<div class="text-xs text-slate-400">{{ $s->email }}</div></td>
                            <td class="{{ $td }}">{{ $assignableRoles[$s->role] ?? str_replace('_', ' ', $s->role) }}</td>
                            <td class="{{ $td }}"><span class="px-2 py-0.5 rounded border text-xs {{ $badge($s->account_status) }}">{{ $s->account_status }}</span></td>
                            <td class="{{ $td }}">{{ $s->last_login_at ? \Illuminate\Support\Carbon::parse($s->last_login_at)->diffForHumans() : '—' }}</td>
                            <td class="{{ $td }} space-x-1 whitespace-nowrap">
                                @if($s->id !== auth()->id() && ($s->role !== 'super_admin' || $isSuperAdmin))
                                    <button wire:click="openAssignRoleModal({{ $s->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Change role</button>
                                    <button wire:click="revokeStaff({{ $s->id }})" wire:confirm="Revoke all staff privileges from this account?" class="{{ $btn }} bg-red-700 hover:bg-red-600 text-white">Revoke</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="{{ $td }} text-center text-slate-400">No staff accounts.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 19. ROLES & PERMISSIONS ================= --}}
        @if($tab === 'roles')
            @unless($isSuperAdmin)
                <div class="mb-3 rounded-xl border border-amber-700 bg-amber-950/50 px-4 py-2 text-sm text-amber-200">Read-only: only a Super Administrator can edit the permission matrix.</div>
            @endunless
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead>
                        <tr>
                            <th class="{{ $th }} sticky left-0 bg-slate-900">Permission</th>
                            @foreach($roles as $role)<th class="{{ $th }} text-center whitespace-nowrap">{{ $role->name }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($permissionsByModule as $module => $perms)
                        <tr class="bg-slate-950/60"><td colspan="{{ $roles->count() + 1 }}" class="px-3 py-1 font-bold uppercase tracking-widest text-[10px] text-rose-300">{{ $module }}</td></tr>
                        @foreach($perms as $perm)
                            <tr class="border-t border-slate-800" wire:key="perm-{{ $perm->id }}">
                                <td class="px-3 py-1.5 font-mono sticky left-0 bg-slate-900">{{ $perm->slug }}</td>
                                @foreach($roles as $role)
                                    @php $granted = $role->permissions->contains('id', $perm->id); $locked = !$isSuperAdmin || in_array($role->slug, ['super_admin', 'admin']); @endphp
                                    <td class="px-3 py-1.5 text-center">
                                        <input type="checkbox" @checked($granted) @disabled($locked)
                                            wire:click="toggleRolePermission({{ $role->id }}, {{ $perm->id }})"
                                            aria-label="{{ $role->name }} – {{ $perm->slug }}"
                                            class="rounded border-slate-600 bg-slate-950 text-rose-600 disabled:opacity-50">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ================= 20. SYSTEM SETTINGS ================= --}}
        @if($tab === 'settings')
            <form wire:submit="saveSystemSettings" class="space-y-4">
                @foreach($systemSettings as $group => $settings)
                    @php $restricted = in_array($group, \App\Livewire\Admin\AdminEnterpriseHub::RESTRICTED_SETTING_GROUPS) && !$isSuperAdmin; @endphp
                    <div class="{{ $card }} p-5">
                        <h2 class="font-bold text-white mb-1 capitalize">{{ $group }}</h2>
                        @if(in_array($group, \App\Livewire\Admin\AdminEnterpriseHub::RESTRICTED_SETTING_GROUPS))
                            <p class="text-xs text-amber-300 mb-3">🔒 Restricted group — Super Administrator only. Gateway secrets are stored in the server environment, never here.</p>
                        @endif
                        <div class="grid md:grid-cols-2 gap-4">
                            @foreach($settings as $setting)
                                <label class="block">
                                    <span class="text-xs font-mono text-slate-300">{{ $setting->key }}</span>
                                    @if($setting->type === 'boolean')
                                        <select wire:model="settingValues.{{ $setting->key }}" class="{{ $input }} mt-1" @disabled($restricted)><option value="true">true</option><option value="false">false</option></select>
                                    @else
                                        <input type="{{ $setting->type === 'integer' ? 'number' : 'text' }}" wire:model="settingValues.{{ $setting->key }}" class="{{ $input }} mt-1" @disabled($restricted)>
                                    @endif
                                    <span class="text-[11px] text-slate-500">{{ $setting->description }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <div class="flex justify-end"><button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save configuration</button></div>
            </form>
        @endif

        {{-- ================= 21. AUDIT LOGS ================= --}}
        @if($tab === 'audit')
            <div class="{{ $card }} p-4 mb-4 grid md:grid-cols-2 gap-3">
                <input type="search" wire:model.live.debounce.400ms="auditActionSearch" placeholder="Filter by action (e.g. listing.approve)…" class="{{ $input }}" aria-label="Filter audit actions">
                <select wire:model.live="auditAdminFilter" class="{{ $input }}" aria-label="Filter by administrator">
                    <option value="">All administrators</option>
                    @foreach($auditAdmins as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
                </select>
            </div>
            <div class="{{ $card }} overflow-x-auto">
                <table class="min-w-full">
                    <thead><tr><th class="{{ $th }}">When</th><th class="{{ $th }}">Administrator</th><th class="{{ $th }}">Action</th><th class="{{ $th }}">Target</th><th class="{{ $th }}">IP</th><th class="{{ $th }}"></th></tr></thead>
                    <tbody>
                    @forelse($auditLogs as $log)
                        <tr class="border-t border-slate-800" wire:key="audit-{{ $log->id }}">
                            <td class="{{ $td }} whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="{{ $td }}">{{ $log->admin?->name ?? 'System' }}</td>
                            <td class="{{ $td }} font-mono text-rose-300">{{ $log->action }}</td>
                            <td class="{{ $td }}">{{ $log->target_type ? class_basename($log->target_type) : '—' }}{{ $log->target_id ? ' #' . $log->target_id : '' }}</td>
                            <td class="{{ $td }} font-mono text-xs">{{ $log->ip_address }}</td>
                            <td class="{{ $td }}"><button wire:click="inspectAuditLog({{ $log->id }})" class="{{ $btn }} bg-slate-700 hover:bg-slate-600 text-white">Inspect</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="{{ $td }} text-center text-slate-400">No audit records.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $auditLogs->links() }}</div>
            </div>
        @endif

        @endif {{-- tabAllowed --}}
    </section>

    {{-- ================================================================= --}}
    {{-- MODALS                                                             --}}
    {{-- ================================================================= --}}
    @php
        $anyModal = $showSuspendUserModal || $showAssignRoleModal || $showRejectListingModal || $showAppealModal
            || $showResolveReportModal || $showRefundModal || $showPlanModal || $showCmsModal || $showSeoModal
            || $showAdModal || $showAuditDetailModal;
    @endphp
    @if($anyModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 p-4" role="dialog" aria-modal="true" wire:keydown.escape.window="closeModals">
            <div class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl">

                @if($showSuspendUserModal)
                    <h3 class="text-lg font-bold text-white mb-4">Suspend Account</h3>
                    <form wire:submit="suspendUser" class="space-y-3">
                        <label class="block text-sm">Reason<textarea wire:model="userSuspendReason" rows="3" class="{{ $input }} mt-1" required></textarea></label>
                        @error('userSuspendReason')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-sm">Type<select wire:model.live="userSuspendType" class="{{ $input }} mt-1"><option value="temporary">Temporary</option><option value="permanent">Permanent</option></select></label>
                            @if($userSuspendType === 'temporary')<label class="block text-sm">Days<input type="number" min="1" max="3650" wire:model="userSuspendDays" class="{{ $input }} mt-1"></label>@endif
                        </div>
                        <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button><button type="submit" class="px-3 py-2 rounded-lg bg-red-600 hover:bg-red-500 text-white text-sm font-semibold">Suspend</button></div>
                    </form>
                @endif

                @if($showAssignRoleModal)
                    <h3 class="text-lg font-bold text-white mb-4">Assign Role</h3>
                    <form wire:submit="assignRole" class="space-y-3">
                        <select wire:model="newRoleSlug" class="{{ $input }}" aria-label="Role">
                            @foreach(($assignableRoles ?? []) as $slug => $name)<option value="{{ $slug }}">{{ $name }}</option>@endforeach
                        </select>
                        @error('newRoleSlug')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button><button type="submit" class="px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save role</button></div>
                    </form>
                @endif

                @if($showRejectListingModal)
                    <h3 class="text-lg font-bold text-white mb-4">Reject Listing</h3>
                    <form wire:submit="rejectListing" class="space-y-3">
                        <label class="block text-sm">Reason shown to the advertiser<textarea wire:model="listingActionReason" rows="3" class="{{ $input }} mt-1" required></textarea></label>
                        @error('listingActionReason')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button><button type="submit" class="px-3 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold">Reject</button></div>
                    </form>
                @endif

                @if($showAppealModal)
                    <h3 class="text-lg font-bold text-white mb-4">Decide Appeal</h3>
                    <div class="space-y-3">
                        <label class="block text-sm">Decision notes<textarea wire:model="appealDecisionNotes" rows="3" class="{{ $input }} mt-1"></textarea></label>
                        @error('appealDecisionNotes')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button>
                            <button type="button" wire:click="decideAppeal('rejected')" class="px-3 py-2 rounded-lg bg-red-600 hover:bg-red-500 text-white text-sm font-semibold">Reject appeal</button>
                            <button type="button" wire:click="decideAppeal('granted')" class="px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold">Grant &amp; reinstate</button>
                        </div>
                    </div>
                @endif

                @if($showResolveReportModal)
                    <h3 class="text-lg font-bold text-white mb-4">Action Report #{{ $targetReportId }}</h3>
                    <div class="space-y-3">
                        <label class="block text-sm">Resolution notes<textarea wire:model="reportResolutionNotes" rows="3" class="{{ $input }} mt-1"></textarea></label>
                        <div class="flex flex-wrap justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button>
                            <button type="button" wire:click="resolveReport('investigating')" class="px-3 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-sm">Investigating</button>
                            <button type="button" wire:click="resolveReport('dismissed')" class="px-3 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-sm">Dismiss</button>
                            <button type="button" wire:click="resolveReport('resolved')" class="px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold">Resolve</button>
                        </div>
                    </div>
                @endif

                @if($showRefundModal)
                    <h3 class="text-lg font-bold text-white mb-4">Issue Refund</h3>
                    <form wire:submit="issueRefund" class="space-y-3">
                        <label class="block text-sm">Reason<input type="text" wire:model="refundReason" class="{{ $input }} mt-1" required maxlength="150"></label>
                        @error('refundReason')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <p class="text-xs text-slate-400">The full transaction amount will be refunded and recorded in the ledger and audit log.</p>
                        <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button><button type="submit" class="px-3 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold">Confirm refund</button></div>
                    </form>
                @endif

                @if($showPlanModal)
                    <h3 class="text-lg font-bold text-white mb-4">{{ $editingPlanId ? 'Edit' : 'New' }} Pricing Plan</h3>
                    <form wire:submit="savePlan" class="space-y-3">
                        <label class="block text-sm">Name<input type="text" wire:model="planForm.name" class="{{ $input }} mt-1"></label>
                        @error('planForm.name')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Slug<input type="text" wire:model="planForm.slug" class="{{ $input }} mt-1" placeholder="auto-generated if blank"></label>
                        @error('planForm.slug')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Description<textarea wire:model="planForm.description" rows="2" class="{{ $input }} mt-1"></textarea></label>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="block text-sm">Price (USD)<input type="number" step="0.01" min="0" wire:model="planForm.price" class="{{ $input }} mt-1"></label>
                            <label class="block text-sm">Days<input type="number" min="1" max="365" wire:model="planForm.duration_days" class="{{ $input }} mt-1"></label>
                            <label class="block text-sm">Type<select wire:model="planForm.plan_type" class="{{ $input }} mt-1"><option value="listing_upgrade">Listing upgrade</option><option value="featured_spot">Featured spot</option><option value="bump">Bump</option><option value="membership">Membership</option></select></label>
                        </div>
                        @error('planForm.price')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="planForm.is_active" class="rounded border-slate-600 bg-slate-950 text-rose-600"> Active</label>
                        <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button><button type="submit" class="px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save plan</button></div>
                    </form>
                @endif

                @if($showSeoModal)
                    <h3 class="text-lg font-bold text-white mb-4">{{ $editingSeoId ? 'Edit' : 'New' }} SEO Entry</h3>
                    <form wire:submit="saveSeo" class="space-y-3">
                        <label class="block text-sm">Route name or path<input type="text" wire:model="seoForm.page_route" class="{{ $input }} mt-1" placeholder="e.g. home or /locations/tx"></label>
                        @error('seoForm.page_route')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Meta title<input type="text" wire:model="seoForm.meta_title" maxlength="150" class="{{ $input }} mt-1"></label>
                        @error('seoForm.meta_title')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Meta description<textarea wire:model="seoForm.meta_description" rows="3" maxlength="320" class="{{ $input }} mt-1"></textarea></label>
                        @error('seoForm.meta_description')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Keywords<input type="text" wire:model="seoForm.meta_keywords" class="{{ $input }} mt-1"></label>
                        <label class="block text-sm">Canonical URL<input type="url" wire:model="seoForm.canonical_url" class="{{ $input }} mt-1"></label>
                        @error('seoForm.canonical_url')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Robots<select wire:model="seoForm.robots_directive" class="{{ $input }} mt-1">@foreach(['index, follow', 'noindex, follow', 'index, nofollow', 'noindex, nofollow'] as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach</select></label>
                        <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button><button type="submit" class="px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save</button></div>
                    </form>
                @endif

                @if($showCmsModal)
                    <h3 class="text-lg font-bold text-white mb-4">{{ $editingCmsId ? 'Edit' : 'New' }} CMS Page</h3>
                    <form wire:submit="saveCmsPage" class="space-y-3">
                        <label class="block text-sm">Title<input type="text" wire:model="cmsForm.title" class="{{ $input }} mt-1"></label>
                        @error('cmsForm.title')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Slug<input type="text" wire:model="cmsForm.slug" class="{{ $input }} mt-1" placeholder="auto-generated if blank"></label>
                        @error('cmsForm.slug')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Content (basic HTML allowed)<textarea wire:model="cmsForm.content" rows="10" class="{{ $input }} mt-1 font-mono"></textarea></label>
                        @error('cmsForm.content')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="cmsForm.is_published" class="rounded border-slate-600 bg-slate-950 text-rose-600"> Published</label>
                        <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button><button type="submit" class="px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save page</button></div>
                    </form>
                @endif

                @if($showAdModal)
                    <h3 class="text-lg font-bold text-white mb-4">{{ $editingAdId ? 'Edit' : 'New' }} Advertisement</h3>
                    <form wire:submit="saveAd" class="space-y-3">
                        <label class="block text-sm">Name<input type="text" wire:model="adForm.name" class="{{ $input }} mt-1"></label>
                        @error('adForm.name')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-sm">Slot<select wire:model="adForm.slot" class="{{ $input }} mt-1">@foreach(\App\Modules\Admin\Models\AdPlacement::SLOTS as $slot)<option value="{{ $slot }}">{{ str_replace('_', ' ', $slot) }}</option>@endforeach</select></label>
                            <label class="block text-sm">Advertiser<input type="text" wire:model="adForm.advertiser_name" class="{{ $input }} mt-1"></label>
                        </div>
                        <label class="block text-sm">Image URL<input type="url" wire:model="adForm.image_url" class="{{ $input }} mt-1"></label>
                        @error('adForm.image_url')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="block text-sm">Target URL<input type="url" wire:model="adForm.target_url" class="{{ $input }} mt-1"></label>
                        @error('adForm.target_url')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block text-sm">Starts<input type="date" wire:model="adForm.starts_at" class="{{ $input }} mt-1"></label>
                            <label class="block text-sm">Ends<input type="date" wire:model="adForm.ends_at" class="{{ $input }} mt-1"></label>
                        </div>
                        @error('adForm.ends_at')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="adForm.is_active" class="rounded border-slate-600 bg-slate-950 text-rose-600"> Active</label>
                        <div class="flex justify-end gap-2 pt-2"><button type="button" wire:click="closeModals" class="px-3 py-2 text-sm text-slate-300">Cancel</button><button type="submit" class="px-3 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-sm font-semibold">Save</button></div>
                    </form>
                @endif

                @if($showAuditDetailModal && isset($inspectedAuditLog) && $inspectedAuditLog)
                    <h3 class="text-lg font-bold text-white mb-1">Audit Record #{{ $inspectedAuditLog->id }}</h3>
                    <p class="text-xs text-slate-400 mb-4"><span class="font-mono text-rose-300">{{ $inspectedAuditLog->action }}</span> · {{ $inspectedAuditLog->admin?->name ?? 'System' }} · {{ $inspectedAuditLog->created_at?->format('Y-m-d H:i:s') }}</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div><div class="text-xs font-bold text-slate-400 mb-1">Before</div><pre class="text-[11px] bg-slate-950 border border-slate-800 rounded-lg p-2 overflow-x-auto whitespace-pre-wrap">{{ json_encode($inspectedAuditLog->before_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'null' }}</pre></div>
                        <div><div class="text-xs font-bold text-slate-400 mb-1">After</div><pre class="text-[11px] bg-slate-950 border border-slate-800 rounded-lg p-2 overflow-x-auto whitespace-pre-wrap">{{ json_encode($inspectedAuditLog->after_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'null' }}</pre></div>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-3">Target: {{ $inspectedAuditLog->target_type ?? '—' }} {{ $inspectedAuditLog->target_id ? '#' . $inspectedAuditLog->target_id : '' }} · IP {{ $inspectedAuditLog->ip_address }}</p>
                    <p class="text-[11px] text-slate-500 break-all">UA: {{ $inspectedAuditLog->user_agent }}</p>
                    <div class="flex justify-end pt-3"><button type="button" wire:click="closeModals" class="px-3 py-2 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-sm">Close</button></div>
                @endif
            </div>
        </div>
    @endif
</div>

