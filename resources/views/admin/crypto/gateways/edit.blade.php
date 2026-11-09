<x-layouts.app title="Configure {{ $gateway->name }} — Admin" description="Edit payment gateway credentials and webhook settings.">
    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Breadcrumbs -->
        <div class="flex items-center space-x-2 text-xs text-slate-500">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-900 transition">Admin Dashboard</a>
            <span>/</span>
            <a href="{{ route('admin.crypto.gateways.index') }}" class="hover:text-slate-900 transition">Gateways</a>
            <span>/</span>
            <span class="text-slate-900 font-bold">Configure {{ $gateway->name }}</span>
        </div>

        <!-- Header -->
        <div class="pb-2 border-b border-slate-200">
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Configure {{ $gateway->name }}</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Credentials are encrypted with AES-256 at rest and never exposed in client scripts.</p>
        </div>

        <form action="{{ route('admin.crypto.gateways.update', $gateway->id) }}" method="POST" class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-xs space-y-6">
            @csrf
            @method('PUT')

            <!-- Name & Status -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Display Name
                    </label>
                    <input type="text" name="name" value="{{ old('name', $gateway->name) }}" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-[#F20B83]" required />
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Environment Mode
                    </label>
                    <select name="environment" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-[#F20B83]">
                        <option value="sandbox" {{ old('environment', $gateway->environment) === 'sandbox' ? 'selected' : '' }}>Sandbox / Testnet</option>
                        <option value="live" {{ old('environment', $gateway->environment) === 'live' ? 'selected' : '' }}>Live Production</option>
                    </select>
                </div>
            </div>

            <!-- Enable Toggle & Display Order -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Status
                    </label>
                    <select name="is_enabled" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-[#F20B83]">
                        <option value="1" {{ old('is_enabled', $gateway->is_enabled) ? 'selected' : '' }}>Enabled (Users can select this gateway)</option>
                        <option value="0" {{ !old('is_enabled', $gateway->is_enabled) ? 'selected' : '' }}>Disabled (Hidden from checkout)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Display Order
                    </label>
                    <input type="number" name="display_order" value="{{ old('display_order', $gateway->display_order) }}" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-[#F20B83]" required />
                </div>
            </div>

            <!-- Provider Specific Credentials -->
            <div class="pt-4 border-t border-slate-100 space-y-4">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">API Credentials & Keys</h3>

                @if($gateway->provider === 'nowpayments')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">NOWPayments API Key</label>
                        <input type="password" name="credentials[api_key]" value="{{ $credentials['api_key'] ?? '' }}" placeholder="Paste your NOWPayments API Key" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-mono text-slate-900 focus:bg-white focus:outline-none focus:border-[#F20B83]" />
                    </div>
                @elseif($gateway->provider === 'coinpayments')
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">CoinPayments Public Key</label>
                            <input type="text" name="credentials[public_key]" value="{{ $credentials['public_key'] ?? '' }}" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-mono text-slate-900 focus:bg-white focus:outline-none focus:border-[#F20B83]" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">CoinPayments Private Key</label>
                            <input type="password" name="credentials[private_key]" value="{{ $credentials['private_key'] ?? '' }}" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-mono text-slate-900 focus:bg-white focus:outline-none focus:border-[#F20B83]" />
                        </div>
                    </div>
                @elseif($gateway->provider === 'coingate')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">CoinGate API Token</label>
                        <input type="password" name="credentials[api_token]" value="{{ $credentials['api_token'] ?? '' }}" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-mono text-slate-900 focus:bg-white focus:outline-none focus:border-[#F20B83]" />
                    </div>
                @elseif($gateway->provider === 'bitpay')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">BitPay API Token</label>
                        <input type="password" name="credentials[api_token]" value="{{ $credentials['api_token'] ?? '' }}" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-mono text-slate-900 focus:bg-white focus:outline-none focus:border-[#F20B83]" />
                    </div>
                @elseif($gateway->provider === 'binance_pay' || $gateway->provider === 'binance-pay')
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Binance Pay API Key (Certificate-SN)</label>
                            <input type="text" name="credentials[api_key]" value="{{ $credentials['api_key'] ?? '' }}" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-mono text-slate-900 focus:bg-white focus:outline-none focus:border-[#F20B83]" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Binance Pay Secret Key</label>
                            <input type="password" name="credentials[secret_key]" value="{{ $credentials['secret_key'] ?? '' }}" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-mono text-slate-900 focus:bg-white focus:outline-none focus:border-[#F20B83]" />
                        </div>
                    </div>
                @elseif($gateway->provider === 'exodus')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Exodus Checkout API Key</label>
                        <input type="password" name="credentials[api_key]" value="{{ $credentials['api_key'] ?? '' }}" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-mono text-slate-900 focus:bg-white focus:outline-none focus:border-[#F20B83]" />
                    </div>
                @endif

                <!-- Webhook Secret -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Webhook / IPN Secret Key</label>
                    <input type="password" name="webhook_secret" value="{{ $webhookSecret ?? '' }}" placeholder="Secret used to verify incoming webhook signatures" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-mono text-slate-900 focus:bg-white focus:outline-none focus:border-[#F20B83]" />
                </div>
            </div>

            <!-- Submit -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <a href="{{ route('admin.crypto.gateways.index') }}" class="text-xs font-bold text-slate-500 hover:text-slate-800">
                    &larr; Cancel
                </a>
                <button type="submit" class="bg-[#F20B83] hover:bg-[#d90858] text-white px-6 py-2.5 rounded-xl text-xs font-extrabold shadow-sm transition">
                    Save Gateway Settings
                </button>
            </div>
        </form>

    </div>
</x-layouts.app>

