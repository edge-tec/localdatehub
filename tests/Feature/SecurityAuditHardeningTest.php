<?php

namespace Tests\Feature;

use App\Livewire\ListingWizard;
use App\Livewire\Messenger\MessengerHub;
use App\Models\User;
use App\Modules\Contact\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityAuditHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_messenger_hub_prevents_idor_on_toggle_reaction_and_set_reply_to(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $attacker = User::factory()->create();

        $message = ContactMessage::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'sender_name' => $sender->name,
            'sender_email_encrypted' => $sender->email,
            'message_encrypted' => 'Private confidential message body',
            'status' => 'sent',
        ]);

        Livewire::actingAs($attacker)
            ->test(MessengerHub::class)
            ->call('toggleReaction', $message->id, '👍')
            ->call('setReplyTo', $message->id)
            ->assertSet('replyToMessageId', null)
            ->assertSet('replyToMessageSnippet', null);

        $message->refresh();
        $this->assertEmpty($message->reactions);
    }

    public function test_listing_wizard_locks_listing_id_property(): void
    {
        $user = User::factory()->create();

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($user)
            ->test(ListingWizard::class)
            ->set('listingId', 999999);
    }

    public function test_social_auth_callback_does_not_fallback_to_stateless_on_invalid_state(): void
    {
        Config::set('services.google.client_id', 'test-client-id');
        Config::set('services.google.client_secret', 'test-client-secret');
        Config::set('services.google.redirect', 'http://localhost/auth/google/callback');

        $provider = \Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('user')->once()->andThrow(new InvalidStateException('Invalid state'));
        $provider->shouldNotReceive('stateless');

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get('/auth/google/callback?code=fake&state=invalid');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest();
    }
}
