<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet\BlockchainNetwork;
use App\Models\Wallet\CryptoAsset;
use App\Models\Wallet\WalletAuditLog;
use Database\Seeders\MainnetBlockchainNetworkSeeder;
use Database\Seeders\TestnetBlockchainNetworkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class MultiChainWalletAndUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mainnet_seeder_creates_disabled_networks_with_verified_contracts(): void
    {
        $this->seed(MainnetBlockchainNetworkSeeder::class);

        $expected = [
            'ETH_MAINNET' => 1,
            'BSC_MAINNET' => 56,
            'POLYGON_MAINNET' => 137,
            'ARBITRUM_MAINNET' => 42161,
            'BASE_MAINNET' => 8453,
        ];

        foreach ($expected as $code => $chainId) {
            $net = BlockchainNetwork::where('network_code', $code)->first();
            $this->assertNotNull($net);
            $this->assertSame($chainId, $net->chain_id);
            $this->assertFalse($net->is_testnet);
            $this->assertFalse($net->is_enabled);
        }

        $ethUsdt = CryptoAsset::whereHas('network', fn ($q) => $q->where('network_code', 'ETH_MAINNET'))
            ->where('symbol', 'USDT')
            ->firstOrFail();
        $this->assertSame('0xdAC17F958D2ee523a2206206994597C13D831ec7', $ethUsdt->contract_address);
        $this->assertSame(6, $ethUsdt->decimals);
    }

    public function test_extended_wallet_api_endpoints_require_auth_and_return_supported_assets(): void
    {
        $this->seed(TestnetBlockchainNetworkSeeder::class);
        BlockchainNetwork::where('network_code', 'ETH_SEPOLIA')->update(['is_enabled' => true]);

        $this->getJson('/api/v1/wallet/supported-assets')->assertStatus(401);
        $this->getJson('/api/v1/wallet/network-status')->assertStatus(401);

        $user = User::factory()->create();
        $this->actingAs($user)
            ->getJson('/api/v1/wallet/supported-assets')
            ->assertOk()
            ->assertJsonFragment(['symbol' => 'ETH']);

        $this->actingAs($user)
            ->getJson('/api/v1/wallet/network-status')
            ->assertOk()
            ->assertJsonFragment(['network_code' => 'ETH_SEPOLIA']);
    }

    public function test_user_crypto_wallet_dashboard_renders_and_admin_blocks_mainnet_in_testnet_mode(): void
    {
        $this->seed(TestnetBlockchainNetworkSeeder::class);
        $this->seed(MainnetBlockchainNetworkSeeder::class);
        BlockchainNetwork::where('network_code', 'ETH_SEPOLIA')->update(['is_enabled' => true]);

        $user = User::factory()->create();
        $this->actingAs($user)
            ->get('/account/crypto-wallet')
            ->assertOk()
            ->assertSee('Multi-Chain Crypto Wallet')
            ->assertSee('ETH_SEPOLIA');

        $this->actingAs($user)
            ->get('/admin/crypto/treasury')
            ->assertStatus(403);

        $admin = User::factory()->create(['role' => 'admin']);
        $mainnet = BlockchainNetwork::where('network_code', 'ETH_MAINNET')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/crypto/treasury')
            ->assertOk()
            ->assertSee('HD Wallet & Treasury Control Panel');

        config()->set('custody.testnet_only', true);
        $this->actingAs($admin)
            ->post("/admin/crypto/treasury/network/{$mainnet->id}/toggle")
            ->assertSessionHas('error');

        $this->assertFalse($mainnet->fresh()->is_enabled);
    }

    public function test_custody_scan_command_skips_mainnet_when_testnet_only_enabled(): void
    {
        $this->seed(TestnetBlockchainNetworkSeeder::class);
        $this->seed(MainnetBlockchainNetworkSeeder::class);

        BlockchainNetwork::where('network_code', 'ETH_SEPOLIA')->update(['is_enabled' => true]);
        BlockchainNetwork::where('network_code', 'ETH_MAINNET')->update(['is_enabled' => true]);

        config()->set('custody.testnet_only', true);
        Bus::fake();

        $this->artisan('custody:scan-networks')->assertExitCode(0);

        $sepolia = BlockchainNetwork::where('network_code', 'ETH_SEPOLIA')->firstOrFail();
        $mainnet = BlockchainNetwork::where('network_code', 'ETH_MAINNET')->firstOrFail();

        Bus::assertDispatched(\App\Jobs\Wallet\ScanBlockchain::class, fn ($job) => $job->networkId === $sepolia->id);
        Bus::assertNotDispatched(\App\Jobs\Wallet\ScanBlockchain::class, fn ($job) => $job->networkId === $mainnet->id);
    }
}
