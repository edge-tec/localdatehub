<?php

namespace App\Http\Controllers;

use App\Modules\Billing\Models\PricingPlan;
use App\Modules\Contact\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PublicPageController extends Controller
{
    /**
     * About LocalDateHub Platform.
     */
    public function about(): View
    {
        return view('pages.about');
    }

    /**
     * Trust, Safety, and Compliance Standards (18+, Anti-Trafficking, FOSTA-SESTA).
     */
    public function safety(): View
    {
        return view('pages.safety');
    }

    /**
     * Show Support & Inquiry Contact Form.
     */
    public function contact(): View
    {
        return view('pages.contact');
    }

    /**
     * Handle Public Support Inquiry Submission.
     */
    public function submitContact(Request $request): RedirectResponse
    {
        $throttleKey = 'contact_submit:' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'message' => "Too many contact inquiries sent. Please wait {$seconds} seconds before trying again.",
            ]);
        }
        RateLimiter::hit($throttleKey, 120);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:25'],
            'inquiry_type' => ['required', 'string', 'in:general_support,safety_report,billing,partnership'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        $contactMsg = ContactMessage::create([
            'sender_id' => $request->user()?->id,
            'inquiry_type' => $validated['inquiry_type'],
            'sender_name' => strip_tags(trim($validated['name'])),
            'sender_email_encrypted' => trim($validated['email']),
            'sender_phone_encrypted' => $validated['phone'] ? strip_tags(trim($validated['phone'])) : null,
            'subject' => strip_tags(trim($validated['subject'])),
            'message_encrypted' => strip_tags(trim($validated['message'])),
            'client_ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        try {
            \App\Modules\Notifications\Models\NotificationLog::create([
                'user_id' => $request->user()?->id,
                'channel' => 'system',
                'notification_type' => 'contact_message_received',
                'subject' => "💬 Contact Form: " . strip_tags(trim($validated['subject'])),
                'message' => "From " . strip_tags(trim($validated['name'])) . " (" . $validated['inquiry_type'] . "): " . \Illuminate\Support\Str::limit(strip_tags(trim($validated['message'])), 120),
                'recipient_target' => 'admin',
                'status' => 'sent',
                'is_read' => false,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {}

        return redirect()->route('pages.contact')->with('success', 'Thank you! Your message has been safely received. Our support team will review and respond promptly.');
    }

    /**
     * Privacy Policy (CCPA, GDPR, Location Fuzzing, Data Retention).
     */
    public function privacy(): View
    {
        return view('pages.privacy');
    }

    /**
     * Terms of Service & Adult User Agreement (18+ Mandatory).
     */
    public function terms(): View
    {
        return view('pages.terms');
    }

    /**
     * Pricing & Feature Spotlight Upgrades.
     */
    public function pricing(): View
    {
        $plans = PricingPlan::where('is_active', true)
            ->orderBy('price_cents')
            ->get();

        return view('pages.pricing', [
            'plans' => $plans,
        ]);
    }
}

