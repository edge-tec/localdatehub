<?php

namespace App\Http\Controllers;

use App\Modules\Billing\Models\PricingPlan;
use App\Modules\Contact\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:25'],
            'inquiry_type' => ['required', 'string', 'in:general_support,safety_report,billing,partnership'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        ContactMessage::create([
            'sender_id' => $request->user()?->id,
            'inquiry_type' => $validated['inquiry_type'],
            'sender_name' => $validated['name'],
            'sender_email_encrypted' => $validated['email'],
            'sender_phone_encrypted' => $validated['phone'] ?? null,
            'subject' => $validated['subject'],
            'message_encrypted' => $validated['message'],
            'client_ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

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
