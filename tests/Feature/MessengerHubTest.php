<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Listings\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_navbar_displays_message_icon_with_unread_count(): void
    {
        $user = User::factory()->create();
        $sender = User::factory()->create();

        ContactMessage::create([
            'sender_id' => $sender->id,
            'recipient_id' => $user->id,
            'sender_name' => $sender->name,
            'sender_email_encrypted' => $sender->email,
            'subject' => 'Inquiry',
            'message_encrypted' => 'Unread message for navbar badge test',
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee(route('account.messages.index'));
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

    public function test_user_can_star_and_unstar_conversation(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $msg = ContactMessage::create([
            'sender_id' => $recipient->id,
            'recipient_id' => $sender->id,
            'sender_name' => $recipient->name,
            'sender_email_encrypted' => $recipient->email,
            'subject' => 'Inquiry',
            'message_encrypted' => 'Hi there',
            'is_read' => true,
            'is_starred' => false,
        ]);

        $test = Livewire::actingAs($sender)
            ->test(MessengerHub::class, ['user' => $recipient->id])
            ->call('toggleStarConversation');

        $this->assertTrue(ContactMessage::where('id', $msg->id)->value('is_starred'));

        $test->call('toggleStarConversation');
        $this->assertFalse(ContactMessage::where('id', $msg->id)->value('is_starred'));
    }

    public function test_user_can_pin_and_unpin_conversation(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $msg = ContactMessage::create([
            'sender_id' => $recipient->id,
            'recipient_id' => $sender->id,
            'sender_name' => $recipient->name,
            'sender_email_encrypted' => $recipient->email,
            'subject' => 'Inquiry',
            'message_encrypted' => 'Hi there',
            'is_read' => true,
            'is_pinned' => false,
        ]);

        $test = Livewire::actingAs($sender)
            ->test(MessengerHub::class, ['user' => $recipient->id])
            ->call('togglePinConversation');

        $this->assertTrue(ContactMessage::where('id', $msg->id)->value('is_pinned'));

        $test->call('togglePinConversation');
        $this->assertFalse(ContactMessage::where('id', $msg->id)->value('is_pinned'));
    }

    public function test_user_can_archive_and_unarchive_conversation(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $msg = ContactMessage::create([
            'sender_id' => $recipient->id,
            'recipient_id' => $sender->id,
            'sender_name' => $recipient->name,
            'sender_email_encrypted' => $recipient->email,
            'subject' => 'Inquiry',
            'message_encrypted' => 'Hi there',
            'is_read' => true,
            'is_archived' => false,
        ]);

        $test = Livewire::actingAs($sender)
            ->test(MessengerHub::class, ['user' => $recipient->id])
            ->call('toggleArchiveConversation');

        $this->assertTrue(ContactMessage::where('id', $msg->id)->value('is_archived'));

        $test->call('toggleArchiveConversation');
        $this->assertFalse(ContactMessage::where('id', $msg->id)->value('is_archived'));
    }

    public function test_user_can_delete_conversation(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $msg = ContactMessage::create([
            'sender_id' => $recipient->id,
            'recipient_id' => $sender->id,
            'sender_name' => $recipient->name,
            'sender_email_encrypted' => $recipient->email,
            'subject' => 'Inquiry',
            'message_encrypted' => 'Delete me',
            'is_read' => true,
        ]);

        Livewire::actingAs($sender)
            ->test(MessengerHub::class, ['user' => $recipient->id])
            ->call('deleteConversation');

        $this->assertNull(ContactMessage::find($msg->id));
    }

    public function test_user_can_block_and_unblock_user(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $test = Livewire::actingAs($sender)
            ->test(MessengerHub::class, ['user' => $recipient->id])
            ->call('confirmBlockUser');

        $isBlocked = DB::table('user_blocks')
            ->where('blocker_id', $sender->id)
            ->where('blocked_id', $recipient->id)
            ->exists();
        $this->assertTrue($isBlocked);

        $test->call('unblockUser');
        $isStillBlocked = DB::table('user_blocks')
            ->where('blocker_id', $sender->id)
            ->where('blocked_id', $recipient->id)
            ->exists();
        $this->assertFalse($isStillBlocked);
    }

    public function test_user_can_submit_report(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        Livewire::actingAs($sender)
            ->test(MessengerHub::class, ['user' => $recipient->id])
            ->set('reportReason', 'harassment')
            ->set('reportNotes', 'Inappropriate messages sent')
            ->call('submitReport');

        $report = DB::table('content_reports')
            ->where('reporter_id', $sender->id)
            ->where('reportable_id', $recipient->id)
            ->first();

        $this->assertNotNull($report);
        $this->assertEquals('harassment', $report->reason_code);
        $this->assertEquals('Inappropriate messages sent', $report->details);
    }
}
