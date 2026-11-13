<?php

namespace Tests\Feature;

use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Notifications\Models\NotificationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ContactSupportPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('contact_submit:127.0.0.1');
    }

    public function test_contact_support_page_renders_redesigned_sections_on_both_routes(): void
    {
        foreach ([route('contact'), route('pages.contact')] as $url) {
            $response = $this->get($url);

            $response->assertStatus(200);
            $response->assertSee("Need Help? We're Here for You!", false);
            $response->assertSee('Have questions about your account, need assistance with your profile, or want to report a concern? Our friendly support team is here to help.', false);
            $response->assertSee('24/7 Support');
            $response->assertSee('Online Support');
            $response->assertSee('Quick Response');
            $response->assertSee('Safe & Secure Community');
            $response->assertSee('Privacy Protection');

            // Left sidebar support categories + Still Need Help + Privacy
            $response->assertSee('Safety & Security');
            $response->assertSee('Billing & Payments');
            $response->assertSee('Account & Profile');
            $response->assertSee('Dating Tips');
            $response->assertSee('FAQs');
            $response->assertSee('Still Need Help?');
            $response->assertSee('Your Privacy Matters');
            $response->assertSee(route('pages.privacy'));
            $response->assertSee(route('pages.terms'));

            // Form & 7 inquiry categories
            $response->assertSee('Send a Support Ticket');
            $response->assertSee('Safety & Reporting');
            $response->assertSee('Dating & Matching');
            $response->assertSee('Technical Issues');
            $response->assertSee('Partnership & Business');
            $response->assertSee('Other Inquiry');

            // Ensure no unsupported End-to-End claim or purple theme accents remain
            $response->assertDontSee('End-to-End Cryptographic Security');
            $response->assertDontSee('to-purple-600');
            $response->assertDontSee('bg-purple-50');
        }
    }

    public function test_all_seven_inquiry_categories_submit_encrypt_and_log_notification(): void
    {
        $categories = [
            'account_profile',
            'billing_payments',
            'safety_reporting',
            'dating_matching',
            'technical_issues',
            'partnership_business',
            'other_inquiry',
        ];

        foreach ($categories as $index => $category) {
            if ($index >= 5) {
                RateLimiter::clear('contact_submit:127.0.0.1');
            }

            $email = "user{$index}@example.com";
            $phone = "+1555000000{$index}";
            $message = "Detailed inquiry for {$category} testing AES-256 encrypted persistence.";

            $response = $this->post(route('pages.contact.submit'), [
                'name' => "Test User {$index}",
                'email' => $email,
                'phone' => $phone,
                'inquiry_type' => $category,
                'subject' => "Subject for {$category}",
                'message' => $message,
            ]);

            $response->assertRedirect(route('pages.contact'));
            $response->assertSessionHas('success');

            $stored = ContactMessage::where('subject', "Subject for {$category}")->first();
            $this->assertNotNull($stored);
            $this->assertSame($category, $stored->inquiry_type);
            $this->assertSame($email, $stored->sender_email_encrypted);
            $this->assertSame($phone, $stored->sender_phone_encrypted);
            $this->assertSame($message, $stored->message_encrypted);

            // Verify raw database values are encrypted rather than stored in plaintext
            $rawRow = DB::table('contact_messages')->where('id', $stored->id)->first();
            $this->assertNotSame($email, $rawRow->sender_email_encrypted);
            $this->assertNotSame($message, $rawRow->message_encrypted);
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('notification_logs')) {
            $this->assertDatabaseHas('notification_logs', [
                'notification_type' => 'contact_message_received',
                'recipient_target' => 'admin',
            ]);
            $this->assertSame(count($categories), NotificationLog::where('notification_type', 'contact_message_received')->count());
        }
    }

    public function test_validation_rejects_invalid_category_and_preserves_old_input(): void
    {
        $response = $this->from(route('pages.contact'))->post(route('pages.contact.submit'), [
            'name' => 'Alex Tester',
            'email' => 'alex@example.com',
            'inquiry_type' => 'invalid_category',
            'subject' => 'Help with account',
            'message' => 'short',
        ]);

        $response->assertRedirect(route('pages.contact'));
        $response->assertSessionHasErrors(['inquiry_type', 'message']);
        $this->assertSame('Alex Tester', session()->getOldInput('name'));
        $this->assertSame('alex@example.com', session()->getOldInput('email'));
        $this->assertSame('Help with account', session()->getOldInput('subject'));
    }

    public function test_turnstile_verification_is_enforced_when_enabled(): void
    {
        Config::set('services.turnstile.enabled', true);
        Config::set('services.turnstile.site_key', 'test-site-key');
        Config::set('services.turnstile.secret_key', 'test-secret-key');

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::sequence()
                ->push(['success' => false], 200)
                ->push(['success' => true], 200),
        ]);

        $failResponse = $this->from(route('pages.contact'))->post(route('pages.contact.submit'), [
            'name' => 'Alex Tester',
            'email' => 'alex@example.com',
            'inquiry_type' => 'account_profile',
            'subject' => 'Turnstile failure test',
            'message' => 'This should fail Turnstile verification cleanly.',
            'cf-turnstile-response' => 'bad-token',
        ]);

        $failResponse->assertSessionHasErrors(['cf-turnstile-response']);

        $passResponse = $this->post(route('pages.contact.submit'), [
            'name' => 'Alex Tester',
            'email' => 'alex@example.com',
            'inquiry_type' => 'account_profile',
            'subject' => 'Turnstile pass test',
            'message' => 'This should pass Turnstile verification cleanly.',
            'cf-turnstile-response' => 'valid-token',
        ]);

        $passResponse->assertRedirect(route('pages.contact'));
        $passResponse->assertSessionHas('success');
    }

    public function test_rate_limiter_blocks_excessive_contact_submissions(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('pages.contact.submit'), [
                'name' => 'Rate Limit User',
                'email' => 'ratelimit@example.com',
                'inquiry_type' => 'other_inquiry',
                'subject' => "Inquiry {$i}",
                'message' => 'Testing rate limiter behavior on contact support.',
            ])->assertRedirect(route('pages.contact'));
        }

        $blocked = $this->from(route('pages.contact'))->post(route('pages.contact.submit'), [
            'name' => 'Rate Limit User',
            'email' => 'ratelimit@example.com',
            'inquiry_type' => 'other_inquiry',
            'subject' => 'Inquiry 6',
            'message' => 'This sixth attempt should be throttled by RateLimiter.',
        ]);

        $blocked->assertSessionHasErrors(['message']);
    }
}
