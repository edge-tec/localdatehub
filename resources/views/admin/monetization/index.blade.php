<x-layouts.app title="Posting & Monetization Control — Admin">
    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8" x-data="{ activeTab: 'settings' }">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <nav class="flex items-center space-x-2 text-xs font-semibold text-slate-500 mb-1">
                    <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-800 transition">Admin Dashboard</a>
                    <span>/</span>
                    <span class="text-slate-900">Posting &amp; Monetization Control</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    Posting Policies, Pricing &amp; Quota Engine
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 mt-1">
                    Centralized administrator management for global posting modes, account-type quotas, individual pricing rules, and verification ranking weights.
                </p>
            </div>

            <!-- Quick Navigation Pills -->
            <div class="flex items-center space-x-2 bg-slate-100 p-1 rounded-2xl text-xs font-bold text-slate-700">
                <button 
                    type="button" 
                    @click="activeTab = 'settings'" 
                    class="px-4 py-2 rounded-xl transition"
                    :class="activeTab === 'settings' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800'"
                >
                    Global Settings
                </button>
                <button 
                    type="button" 
                    @click="activeTab = 'account_limits'" 
                    class="px-4 py-2 rounded-xl transition"
                    :class="activeTab === 'account_limits' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800'"
                >
                    Account Quotas
                </button>
                <button 
                    type="button" 
                    @click="activeTab = 'pricing_rules'" 
                    class="px-4 py-2 rounded-xl transition"
                    :class="activeTab === 'pricing_rules' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800'"
                >
                    Category Pricing
                </button>
                <button 
                    type="button" 
                    @click="activeTab = 'user_overrides'" 
                    class="px-4 py-2 rounded-xl transition"
                    :class="activeTab === 'user_overrides' ? 'bg-white shadow-xs text-slate-900' : 'text-slate-500 hover:text-slate-800'"
                >
                    User Overrides
                </button>
            </div>
        </div>

        <!-- TAB 1: Global Posting & Moderation Settings -->
        <div x-show="activeTab === 'settings'" class="space-y-6">
            <form action="{{ route('admin.monetization.settings.update') }}" method="POST" class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <h2 class="text-lg font-black text-slate-900">Global Monetization &amp; Approval Policy</h2>
                    <p class="text-xs text-slate-500">Configure whether posting requires credits and whether listings require human review.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2 border-t border-slate-100">
                    <!-- Global Posting Mode -->
                    <div class="space-y-1.5">
                        <label for="global_posting_mode" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Global Posting Mode *
                        </label>
                        <select 
                            name="global_posting_mode" 
                            id="global_posting_mode" 
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs text-slate-900 font-semibold focus:bg-white focus:border-[#F20B83] focus:outline-none"
                        >
                            <option value="quota_then_paid" {{ $settings->global_posting_mode === 'quota_then_paid' ? 'selected' : '' }}>
                                Free Quota Followed by Paid (Recommended)
                            </option>
                            <option value="free" {{ $settings->global_posting_mode === 'free' ? 'selected' : '' }}>
                                All Posts Free (No Credits Required)
                            </option>
                            <option value="paid" {{ $settings->global_posting_mode === 'paid' ? 'selected' : '' }}>
                                All Posts Paid (Every Ad Costs Credits)
                            </option>
                            <option value="account_based" {{ $settings->global_posting_mode === 'account_based' ? 'selected' : '' }}>
                                Account-Type Based (Configured per role)
                            </option>
                            <option value="category_based" {{ $settings->global_posting_mode === 'category_based' ? 'selected' : '' }}>
                                Category-Based Pricing Rules
                            </option>
                        </select>
                    </div>

                    <!-- Approval Policy Mode -->
                    <div class="space-y-1.5">
                        <label for="approval_mode" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Approval Moderation Mode *
                        </label>
                        <select 
                            name="approval_mode" 
                            id="approval_mode" 
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs text-slate-900 font-semibold focus:bg-white focus:border-[#F20B83] focus:outline-none"
                        >
                            <option value="conditional" {{ $settings->approval_mode === 'conditional' ? 'selected' : '' }}>
                                Conditional (New accounts manual, verified accounts auto)
                            </option>
                            <option value="auto" {{ $settings->approval_mode === 'auto' ? 'selected' : '' }}>
                                Automatic Approval (Publish all compliant ads immediately)
                            </option>
                            <option value="manual" {{ $settings->approval_mode === 'manual' ? 'selected' : '' }}>
                                All Posts Manual Review (All ads queue for moderator approval)
                            </option>
                        </select>
                    </div>

                    <!-- Default Price -->
                    <div class="space-y-1.5">
                        <label for="default_post_price_credits" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Default Post Price (Credits) *
                        </label>
                        <input 
                            type="number" 
                            name="default_post_price_credits" 
                            id="default_post_price_credits" 
                            value="{{ $settings->default_post_price_credits }}"
                            min="0"
                            required
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs text-slate-900 font-semibold focus:bg-white focus:border-[#F20B83] focus:outline-none"
                        >
                    </div>

                    <!-- Default Free Quota -->
                    <div class="space-y-1.5">
                        <label for="default_free_quota" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Default Free Monthly Allowance *
                        </label>
                        <input 
                            type="number" 
                            name="default_free_quota" 
                            id="default_free_quota" 
                            value="{{ $settings->default_free_quota }}"
                            min="0"
                            required
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs text-slate-900 font-semibold focus:bg-white focus:border-[#F20B83] focus:outline-none"
                        >
                    </div>

                    <!-- Daily Limit -->
                    <div class="space-y-1.5">
                        <label for="daily_limit" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Daily Post Limit (Global Default)
                        </label>
                        <input 
                            type="number" 
                            name="daily_limit" 
                            id="daily_limit" 
                            value="{{ $settings->daily_limit }}"
                            min="1"
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs text-slate-900 font-semibold focus:bg-white focus:border-[#F20B83] focus:outline-none"
                        >
                    </div>

                    <!-- Monthly Limit -->
                    <div class="space-y-1.5">
                        <label for="monthly_limit" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Monthly Post Limit (Global Default)
                        </label>
                        <input 
                            type="number" 
                            name="monthly_limit" 
                            id="monthly_limit" 
                            value="{{ $settings->monthly_limit }}"
                            min="1"
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs text-slate-900 font-semibold focus:bg-white focus:border-[#F20B83] focus:outline-none"
                        >
                    </div>

                    <!-- Verified Ranking Boost Score -->
                    <div class="space-y-1.5">
                        <label for="verified_user_ranking_boost" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Verified User Search Ranking Advantage Score *
                        </label>
                        <input 
                            type="number" 
                            name="verified_user_ranking_boost" 
                            id="verified_user_ranking_boost" 
                            value="{{ $settings->verified_user_ranking_boost }}"
                            min="0"
                            max="1000"
                            required
                            class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs text-slate-900 font-semibold focus:bg-white focus:border-[#F20B83] focus:outline-none"
                        >
                        <p class="text-[11px] text-slate-400">Positioning multiplier given to verified poster listings.</p>
                    </div>

                    <!-- Checkboxes -->
                    <div class="space-y-3 pt-3">
                        <label class="flex items-center space-x-2.5 cursor-pointer">
                            <input type="checkbox" name="unlimited_posting_enabled" value="1" {{ $settings->unlimited_posting_enabled ? 'checked' : '' }} class="rounded text-[#F20B83] focus:ring-[#F20B83]">
                            <span class="text-xs font-bold text-slate-800">Enable Unlimited Posting Globally</span>
                        </label>
                        <label class="flex items-center space-x-2.5 cursor-pointer">
                            <input type="checkbox" name="new_account_manual_review" value="1" {{ $settings->new_account_manual_review ? 'checked' : '' }} class="rounded text-[#F20B83] focus:ring-[#F20B83]">
                            <span class="text-xs font-bold text-slate-800">Require Manual Review for New Accounts</span>
                        </label>
                        <label class="flex items-center space-x-2.5 cursor-pointer">
                            <input type="checkbox" name="verified_user_auto_approve" value="1" {{ $settings->verified_user_auto_approve ? 'checked' : '' }} class="rounded text-[#F20B83] focus:ring-[#F20B83]">
                            <span class="text-xs font-bold text-slate-800">Auto-Approve Verified Member Listings</span>
                        </label>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="bg-[#F20B83] hover:bg-[#d90858] text-white px-8 py-2.5 rounded-full text-xs font-bold shadow-md transition">
                        Save Global Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- TAB 2: Account-Type Quotas & Limits -->
        <div x-show="activeTab === 'account_limits'" class="space-y-6">
            <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                <div>
                    <h2 class="text-lg font-black text-slate-900">Account-Type Quotas &amp; Limits</h2>
                    <p class="text-xs text-slate-500">Configure posting quotas, daily/monthly ceilings, and pricing for each account tier.</p>
                </div>

                <div class="space-y-6">
                    @foreach(['new_account', 'regular', 'verified', 'premium', 'business'] as $tier)
                        @php $limit = $accountLimits[$tier] ?? null; @endphp
                        <form action="{{ route('admin.monetization.limits.update', $tier) }}" method="POST" class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-4">
                            @csrf
                            @method('PUT')
                            <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="w-3 h-3 rounded-full bg-[#F20B83]"></span>
                                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider">{{ str_replace('_', ' ', $tier) }}</h3>
                                </div>
                                <label class="flex items-center space-x-2 text-xs font-bold text-slate-700">
                                    <input type="checkbox" name="is_unlimited" value="1" {{ $limit?->is_unlimited ? 'checked' : '' }} class="rounded text-[#F20B83]">
                                    <span>Unlimited Posting</span>
                                </label>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Free Post Quota:</label>
                                    <input type="number" name="free_post_quota" value="{{ $limit?->free_post_quota ?? 3 }}" min="0" required class="w-full bg-white border border-slate-300 rounded-xl px-3 py-1.5 font-bold">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Daily Limit:</label>
                                    <input type="number" name="daily_limit" value="{{ $limit?->daily_limit ?? 5 }}" min="0" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-1.5 font-bold">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Monthly Limit:</label>
                                    <input type="number" name="monthly_limit" value="{{ $limit?->monthly_limit ?? 50 }}" min="0" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-1.5 font-bold">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Price (Credits):</label>
                                    <input type="number" name="post_price_credits" value="{{ $limit?->post_price_credits }}" placeholder="Inherit global" min="0" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-1.5 font-bold">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Approval Policy:</label>
                                    <select name="approval_mode" class="w-full bg-white border border-slate-300 rounded-xl px-2 py-1.5 font-semibold text-xs">
                                        <option value="inherit" {{ ($limit?->approval_mode ?? 'inherit') === 'inherit' ? 'selected' : '' }}>Inherit</option>
                                        <option value="auto" {{ ($limit?->approval_mode ?? '') === 'auto' ? 'selected' : '' }}>Auto-Approve</option>
                                        <option value="manual" {{ ($limit?->approval_mode ?? '') === 'manual' ? 'selected' : '' }}>Manual Review</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-1.5 rounded-xl text-xs font-bold transition">
                                    Update {{ ucfirst(str_replace('_', ' ', $tier)) }}
                                </button>
                            </div>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- TAB 3: Category & Account Specific Pricing Rules -->
        <div x-show="activeTab === 'pricing_rules'" class="space-y-6">
            <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-lg font-black text-slate-900">Category &amp; Account Pricing Rules</h2>
                        <p class="text-xs text-slate-500">Fine-grained pricing overrides. Applied based on priority rank.</p>
                    </div>

                    <!-- Add Rule Trigger -->
                    <button 
                        type="button" 
                        @click="$dispatch('open-add-pricing-rule')"
                        class="bg-[#F20B83] hover:bg-[#d90858] text-white px-4 py-2 rounded-xl text-xs font-bold shadow-xs transition"
                    >
                        + Add Pricing Rule
                    </button>
                </div>

                <!-- Add Rule Form (inline collapsible) -->
                <form action="{{ route('admin.monetization.pricing.store') }}" method="POST" class="bg-pink-50/40 border border-pink-200/80 rounded-2xl p-5 space-y-4">
                    @csrf
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Create New Pricing Override</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 text-xs">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Target Category:</label>
                            <select name="category_id" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 font-semibold">
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @foreach($cat->children as $child)
                                        <option value="{{ $child->id }}">&nbsp;&nbsp;↳ {{ $child->name }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Target Account Tier:</label>
                            <select name="account_type" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 font-semibold">
                                <option value="">All Account Types</option>
                                <option value="new_account">New Account</option>
                                <option value="regular">Regular</option>
                                <option value="verified">Verified</option>
                                <option value="premium">Premium</option>
                                <option value="business">Business</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Price (Credits):</label>
                            <input type="number" name="price_credits" value="10" min="0" required class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 font-bold">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Priority Rank (1-100):</label>
                            <input type="number" name="priority" value="20" min="1" max="100" required class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 font-bold">
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white py-2 rounded-xl text-xs font-bold transition">
                                Save Rule
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Rules Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-black uppercase tracking-wider text-slate-600">
                            <tr>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Account Tier</th>
                                <th class="px-4 py-3">Price</th>
                                <th class="px-4 py-3">Priority</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($pricingRules as $rule)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-4 py-3 font-bold text-slate-900">{{ $rule->category?->name ?: 'All Categories' }}</td>
                                    <td class="px-4 py-3">{{ $rule->account_type ? ucfirst(str_replace('_', ' ', $rule->account_type)) : 'All Account Types' }}</td>
                                    <td class="px-4 py-3 font-black text-[#F20B83]">{{ $rule->is_free ? 'FREE' : $rule->price_credits . ' Credits' }}</td>
                                    <td class="px-4 py-3 font-mono font-bold">{{ $rule->priority }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <form action="{{ route('admin.monetization.pricing.delete', $rule) }}" method="POST" class="inline" onsubmit="return confirm('Delete this pricing rule?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:underline font-bold text-[11px]">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-slate-400 italic">No custom category pricing rules created yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 4: User Posting Overrides -->
        <div x-show="activeTab === 'user_overrides'" class="space-y-6">
            <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                <div>
                    <h2 class="text-lg font-black text-slate-900">Individual User Overrides</h2>
                    <p class="text-xs text-slate-500">Assign custom posting limits, free allowances, custom prices, or restrictions for specific users.</p>
                </div>

                <!-- Form to Add Override -->
                <form action="{{ route('admin.monetization.overrides.store') }}" method="POST" class="bg-slate-50 rounded-2xl p-5 border border-slate-200 space-y-4">
                    @csrf
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Set Individual User Override</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">User ID *:</label>
                            <input type="number" name="user_id" placeholder="Enter User ID" required class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 font-bold">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Free Allowance:</label>
                            <input type="number" name="free_post_allowance" placeholder="Free posts" min="0" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 font-bold">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Custom Price (Credits):</label>
                            <input type="number" name="custom_price_credits" placeholder="e.g. 0 for free" min="0" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 font-bold">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Approval Mode:</label>
                            <select name="approval_mode" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 font-semibold">
                                <option value="inherit">Inherit</option>
                                <option value="auto">Auto-Approve Always</option>
                                <option value="manual">Require Review Always</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center space-x-6 text-xs pt-1">
                        <label class="flex items-center space-x-2 font-bold text-slate-800 cursor-pointer">
                            <input type="checkbox" name="is_unlimited" value="1" class="rounded text-[#F20B83]">
                            <span>Unlimited Posting Privilege</span>
                        </label>

                        <label class="flex items-center space-x-2 font-bold text-rose-700 cursor-pointer">
                            <input type="checkbox" name="is_posting_restricted" value="1" class="rounded text-rose-600">
                            <span>Temporarily Restrict/Block Posting</span>
                        </label>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-6 py-2 rounded-xl text-xs font-bold transition">
                            Apply User Override
                        </button>
                    </div>
                </form>

                <!-- Existing Overrides Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-black uppercase tracking-wider text-slate-600">
                            <tr>
                                <th class="px-4 py-3">User</th>
                                <th class="px-4 py-3">Free Allowance</th>
                                <th class="px-4 py-3">Price</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($userOverrides as $ov)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-900">{{ $ov->user?->name }} (ID: {{ $ov->user_id }})</div>
                                        <div class="text-[10px] text-slate-400">{{ $ov->user?->email }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-semibold">{{ $ov->is_unlimited ? 'Unlimited' : ($ov->free_post_allowance !== null ? $ov->free_post_allowance . ' posts' : 'Default') }}</td>
                                    <td class="px-4 py-3 font-bold text-[#F20B83]">{{ $ov->custom_price_credits !== null ? $ov->custom_price_credits . ' Credits' : 'Default' }}</td>
                                    <td class="px-4 py-3">
                                        @if($ov->is_posting_restricted)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">Restricted</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">Active</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <form action="{{ route('admin.monetization.overrides.delete', $ov) }}" method="POST" class="inline" onsubmit="return confirm('Remove override for this user?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:underline font-bold text-[11px]">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-slate-400 italic">No individual user overrides currently configured.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($userOverrides->hasPages())
                    <div class="pt-4 border-t border-slate-100">
                        {{ $userOverrides->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>
</x-layouts.app>

