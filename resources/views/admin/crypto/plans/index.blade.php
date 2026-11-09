<x-layouts.app title="Boost Plans Management — Admin" description="Manage post boost packages and promotion pricing on LocalDateHub.">
    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6" x-data="{ showCreateModal: false, editingPlan: null }">

        <!-- Breadcrumbs -->
        <div class="flex items-center space-x-2 text-xs text-slate-500">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-900 transition">Admin Dashboard</a>
            <span>/</span>
            <span class="text-slate-900 font-bold">Boost Plans</span>
        </div>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-slate-200">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Boost Plans Management</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Configure pricing tiers, durations, priority levels, and promotion features</p>
            </div>
            <button 
                type="button" 
                @click="showCreateModal = true" 
                class="bg-[#F20B83] hover:bg-[#d90858] text-white text-xs font-bold px-4 py-2.5 rounded-xl transition shadow-xs flex items-center gap-1.5 self-start"
            >
                <span>+ Create New Plan</span>
            </button>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold">
                ✓ {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold">
                ✕ {{ session('error') }}
            </div>
        @endif

        <!-- Plans Table -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200 text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Plan Name</th>
                            <th class="py-3 px-4">Duration</th>
                            <th class="py-3 px-4">Price (USD)</th>
                            <th class="py-3 px-4">Position / Placement</th>
                            <th class="py-3 px-4">Priority</th>
                            <th class="py-3 px-4">Total Sold</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($plans as $plan)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4">
                                    <div class="font-extrabold text-slate-900 text-sm">{{ $plan->name }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $plan->slug }}</div>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-800">
                                    {{ $plan->duration_days }} {{ Str::plural('Day', $plan->duration_days) }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="text-sm font-black text-[#F20B83]">${{ number_format((float) $plan->price, 2) }}</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-bold">
                                        {{ str_replace('_', ' ', $plan->featured_position) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-700">
                                    Lvl {{ $plan->priority_level }}
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-900">
                                    {{ $plan->boosts_count }} boosts
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($plan->is_active)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-700">
                                            Active
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-slate-100 text-slate-500">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right space-x-2 whitespace-nowrap">
                                    <button 
                                        type="button" 
                                        @click="editingPlan = {{ json_encode($plan) }}"
                                        class="text-xs font-bold text-blue-600 hover:underline"
                                    >
                                        Edit
                                    </button>

                                    @if($plan->boosts_count === 0 && $plan->payments_count === 0)
                                        <form action="{{ route('admin.crypto.plans.destroy', $plan->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete plan {{ $plan->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-bold text-rose-600 hover:underline">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create Modal -->
        <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-black text-slate-900">Create New Boost Plan</h3>
                    <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
                </div>

                <form action="{{ route('admin.crypto.plans.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Plan Name</label>
                        <input type="text" name="name" placeholder="e.g. 7 Days VIP Spotlight" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" required />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Price (USD)</label>
                            <input type="number" step="0.01" name="price" placeholder="19.99" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" required />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Duration (Days)</label>
                            <input type="number" name="duration_days" placeholder="7" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Placement Position</label>
                            <select name="featured_position" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold">
                                <option value="top_of_search">Top of Search Results</option>
                                <option value="homepage_and_category">Homepage & Category Pinned</option>
                                <option value="urgent_spotlight">Urgent VIP Spotlight</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Priority Level (1-10)</label>
                            <input type="number" name="priority_level" value="1" min="1" max="10" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Status</label>
                            <select name="is_active" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Display Order</label>
                            <input type="number" name="display_order" value="1" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" required />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Description</label>
                        <textarea name="description" rows="2" placeholder="Benefits displayed to posters at checkout" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs"></textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-[#F20B83] text-white rounded-xl hover:bg-[#d90858]">Save Plan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div x-show="editingPlan" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-black text-slate-900">Edit Boost Plan</h3>
                    <button type="button" @click="editingPlan = null" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
                </div>

                <form :action="'{{ url('admin/crypto/plans') }}/' + (editingPlan ? editingPlan.id : '')" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Plan Name</label>
                        <input type="text" name="name" :value="editingPlan?.name" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" required />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Price (USD)</label>
                            <input type="number" step="0.01" name="price" :value="editingPlan?.price" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" required />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Duration (Days)</label>
                            <input type="number" name="duration_days" :value="editingPlan?.duration_days" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Placement Position</label>
                            <select name="featured_position" x-model="editingPlan.featured_position" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold">
                                <option value="top_of_search">Top of Search Results</option>
                                <option value="homepage_and_category">Homepage & Category Pinned</option>
                                <option value="urgent_spotlight">Urgent VIP Spotlight</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Priority Level</label>
                            <input type="number" name="priority_level" :value="editingPlan?.priority_level" min="1" max="10" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Status</label>
                            <select name="is_active" :value="editingPlan?.is_active ? '1' : '0'" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Display Order</label>
                            <input type="number" name="display_order" :value="editingPlan?.display_order" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" required />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Description</label>
                        <textarea name="description" rows="2" x-text="editingPlan?.description" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs"></textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" @click="editingPlan = null" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold bg-[#F20B83] text-white rounded-xl hover:bg-[#d90858]">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-layouts.app>

