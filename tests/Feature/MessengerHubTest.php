<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Listings\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Livewire\Messenger\MessengerHub;

class MessengerHubTest extends TestCase
{
    use RefreshDatabase;
    public function test_authenticated_user_can_access_messenger(): void
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->get(route('account.messages.index'));
        $response->assertStatus(200);
        $response->assertSeeLivewire(MessengerHub::class);
    }

    public function test_user_can_send_message_in_messenger(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        Livewire::actingAs($sender)
            ->test(MessengerHub::class, [
                'user' => $recipient->id,
            ])
            ->assertSet('activePeerId', $recipient->id)
            ->set('messageText', 'Hello from automated messenger test!')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $latest = ContactMessage::latest('id')->first();
        $this->assertNotNull($latest);
        $this->assertEquals($sender->id, $latest->sender_id);
        $this->assertEquals($recipient->id, $latest->recipient_id);
        $this->assertEquals('Hello from automated messenger test!', $latest->message_encrypted);
    }

    public function test_user_can_toggle_reaction_on_message(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $msg = ContactMessage::create([
            'sender_id' => $recipient->id,
            'recipient_id' => $sender->id,
            'sender_name' => $recipient->name,
            'sender_email_encrypted' => $recipient->email,
            'subject' => 'Inquiry',
            'message_encrypted' => 'How are you?',
            'is_read' => true,
        ]);

        Livewire::actingAs($sender)
            ->test(MessengerHub::class, ['conversation' => $msg->id])
            ->call('toggleReaction', $msg->id, '❤️')
            ->assertHasNoErrors();

        $msg->refresh();
        $this->assertArrayHasKey('❤️', $msg->reactions);
        $this->assertContains($sender->id, $msg->reactions['❤️']);
    }
}
