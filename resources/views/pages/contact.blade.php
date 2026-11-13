@extends('layouts.app')

@section('title', 'Contact Support & Help Desk - LocalDateHub')
@section('meta_description', 'Need help with your LocalDateHub account, profile, billing, or safety? Contact our 24/7 support team or browse helpful FAQs and dating tips.')

@section('content')
@php
    $initialTopic = old('inquiry_type', 'account_profile');
    $legacyMap = [
        'general_support' => 'account_profile',
        'safety_report' => 'safety_reporting',
        'billing' => 'billing_payments',
        'partnership' => 'partnership_business',
    ];
    if (isset($legacyMap[$initialTopic])) {
        $initialTopic = $legacyMap[$initialTopic];
    }
@endphp

<div
    class="bg-slate-50/70 min-h-screen py-6 sm:py-10"
    x-data="{
        activeCategory: 'contact_support',
        selectedTopic: '{{ $initialTopic }}',
        openFaq: 0,
        submitting: false,
        messageLength: {{ strlen((string) old('message', '')) }},
        selectCategory(category, topic = null) {
            this.activeCategory = category;
            if (topic) {
                this.selectedTopic = topic;
            }
            if (category === 'contact_support' || category === 'safety_security' || category === 'billing_payments' || category === 'account_profile') {
                const formEl = document.getElementById('support-ticket-form');
                if (formEl && window.innerWidth < 1024) {
                    formEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        }
    }"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">

        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs font-semibold text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-[#F20B65] transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Home
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-[#F20B65]">Contact Support</span>
        </nav>

        <!-- Compact Modern Hero Section -->
        <section class="relative overflow-hidden bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6 sm:p-8 lg:p-10">
            <!-- Top Pink-to-Orange Accent Bar -->
            <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-[#F20B65] via-pink-500 to-orange-500"></div>

            <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="max-w-2xl space-y-4">
                    <!-- Status Pills -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-pink-50 border border-pink-200/80 text-[#F20B65] text-[11px] font-bold uppercase tracking-wider">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" />
                            </svg>
                            24/7 Support
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-bold uppercase tracking-wider">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Online Support
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight leading-tight">
                        Need Help? We're Here for You!
                    </h1>

                    <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                        Have questions about your account, need assistance with your profile, or want to report a concern? Our friendly support team is here to help.
                    </p>

                    <!-- Trust & Service Indicators -->
                    <div class="flex flex-wrap items-center gap-2.5 pt-1">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-orange-50/80 border border-orange-200/80 text-xs font-semibold text-orange-800">
                            <svg class="w-3.5 h-3.5 text-orange-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Quick Response
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700">
                            <svg class="w-3.5 h-3.5 text-[#F20B65] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Safe &amp; Secure Community
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50/70 border border-emerald-200/80 text-xs font-semibold text-emerald-800">
                            <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Privacy Protection
                        </span>
                    </div>
                </div>

                <!-- Tasteful Dating-Themed Heart Visual -->
                <div class="hidden lg:flex items-center justify-center shrink-0">
                    <div class="relative w-28 h-28 rounded-2xl bg-gradient-to-br from-pink-50 via-rose-50 to-orange-50 border border-pink-200/70 flex items-center justify-center shadow-xs">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#F20B65] to-orange-500 flex items-center justify-center text-white shadow-md shadow-pink-500/20">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Two-Column Support Dashboard -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">

            <!-- Left Sidebar: Support Categories + Help + Privacy -->
            <aside class="lg:col-span-4 space-y-5">

                <!-- Support Categories Card -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider">Support Categories</h2>
                        <span class="text-[11px] font-semibold text-slate-500">Select a topic</span>
                    </div>

                    <div class="space-y-2.5" role="list">
                        <!-- 1. Contact Support -->
                        <button
                            type="button"
                            @click="selectCategory('contact_support', 'other_inquiry')"
                            :aria-pressed="activeCategory === 'contact_support'"
                            :class="activeCategory === 'contact_support'
                                ? 'border-[#F20B65] bg-pink-50/70 ring-2 ring-pink-500/15'
                                : 'border-slate-200 bg-white hover:border-pink-300 hover:bg-slate-50/80'"
                            class="w-full text-left p-3.5 rounded-xl border transition-all duration-150 flex items-start gap-3 focus:outline-none focus:ring-2 focus:ring-[#F20B65]"
                        >
                            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#F20B65] to-orange-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-bold text-slate-900">Contact Support</span>
                                <span class="block text-xs text-slate-600 mt-0.5 leading-snug">Send us a message.</span>
                            </span>
                        </button>

                        <!-- 2. Safety & Security -->
                        <button
                            type="button"
                            @click="selectCategory('safety_security', 'safety_reporting')"
                            :aria-pressed="activeCategory === 'safety_security'"
                            :class="activeCategory === 'safety_security'
                                ? 'border-[#F20B65] bg-pink-50/70 ring-2 ring-pink-500/15'
                                : 'border-slate-200 bg-white hover:border-pink-300 hover:bg-slate-50/80'"
                            class="w-full text-left p-3.5 rounded-xl border transition-all duration-150 flex items-start gap-3 focus:outline-none focus:ring-2 focus:ring-[#F20B65]"
                        >
                            <span class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-slate-900">Safety &amp; Security</span>
                                    <span class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded bg-rose-100 text-rose-700">Priority</span>
                                </span>
                                <span class="block text-xs text-slate-600 mt-0.5 leading-snug">Report suspicious activity, harassment, fake profiles, or abuse.</span>
                            </span>
                        </button>

                        <!-- 3. Billing & Payments -->
                        <button
                            type="button"
                            @click="selectCategory('billing_payments', 'billing_payments')"
                            :aria-pressed="activeCategory === 'billing_payments'"
                            :class="activeCategory === 'billing_payments'
                                ? 'border-[#F20B65] bg-pink-50/70 ring-2 ring-pink-500/15'
                                : 'border-slate-200 bg-white hover:border-pink-300 hover:bg-slate-50/80'"
                            class="w-full text-left p-3.5 rounded-xl border transition-all duration-150 flex items-start gap-3 focus:outline-none focus:ring-2 focus:ring-[#F20B65]"
                        >
                            <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-bold text-slate-900">Billing &amp; Payments</span>
                                <span class="block text-xs text-slate-600 mt-0.5 leading-snug">Questions about payments, subscriptions, and transactions.</span>
                            </span>
                        </button>

                        <!-- 4. Account & Profile -->
                        <button
                            type="button"
                            @click="selectCategory('account_profile', 'account_profile')"
                            :aria-pressed="activeCategory === 'account_profile'"
                            :class="activeCategory === 'account_profile'
                                ? 'border-[#F20B65] bg-pink-50/70 ring-2 ring-pink-500/15'
                                : 'border-slate-200 bg-white hover:border-pink-300 hover:bg-slate-50/80'"
                            class="w-full text-left p-3.5 rounded-xl border transition-all duration-150 flex items-start gap-3 focus:outline-none focus:ring-2 focus:ring-[#F20B65]"
                        >
                            <span class="w-9 h-9 rounded-xl bg-pink-50 text-[#F20B65] border border-pink-200 flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-bold text-slate-900">Account &amp; Profile</span>
                                <span class="block text-xs text-slate-600 mt-0.5 leading-snug">Account access, profile settings, and verification.</span>
                            </span>
                        </button>

                        <!-- 5. Dating Tips -->
                        <button
                            type="button"
                            @click="selectCategory('dating_tips', 'dating_matching')"
                            :aria-pressed="activeCategory === 'dating_tips'"
                            :class="activeCategory === 'dating_tips'
                                ? 'border-[#F20B65] bg-pink-50/70 ring-2 ring-pink-500/15'
                                : 'border-slate-200 bg-white hover:border-pink-300 hover:bg-slate-50/80'"
                            class="w-full text-left p-3.5 rounded-xl border transition-all duration-150 flex items-start gap-3 focus:outline-none focus:ring-2 focus:ring-[#F20B65]"
                        >
                            <span class="w-9 h-9 rounded-xl bg-orange-50 text-orange-600 border border-orange-200 flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-bold text-slate-900">Dating Tips</span>
                                <span class="block text-xs text-slate-600 mt-0.5 leading-snug">Help with using the platform.</span>
                            </span>
                        </button>

                        <!-- 6. FAQs -->
                        <button
                            type="button"
                            @click="selectCategory('faqs')"
                            :aria-pressed="activeCategory === 'faqs'"
                            :class="activeCategory === 'faqs'
                                ? 'border-[#F20B65] bg-pink-50/70 ring-2 ring-pink-500/15'
                                : 'border-slate-200 bg-white hover:border-pink-300 hover:bg-slate-50/80'"
                            class="w-full text-left p-3.5 rounded-xl border transition-all duration-150 flex items-start gap-3 focus:outline-none focus:ring-2 focus:ring-[#F20B65]"
                        >
                            <span class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center shrink-0">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-bold text-slate-900">FAQs</span>
                                <span class="block text-xs text-slate-600 mt-0.5 leading-snug">Find answers to common questions.</span>
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Compact "Still Need Help?" Card -->
                <div class="bg-gradient-to-br from-pink-50/90 via-white to-orange-50/80 rounded-2xl border border-pink-200/80 shadow-xs p-5 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-black text-slate-900">Still Need Help?</h3>
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Online
                        </span>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Send a ticket to our 24/7 operations team or review our community safety resources for immediate guidance.
                    </p>
                    <div class="flex flex-wrap gap-2 pt-1">
                        <a
                            href="#support-ticket-form"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-gradient-to-r from-[#F20B65] to-orange-500 text-white text-xs font-bold shadow-xs hover:opacity-95 transition"
                        >
                            Write a Ticket
                        </a>
                        <a
                            href="{{ route('pages.safety') }}"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-xs font-bold hover:border-pink-300 hover:text-[#F20B65] transition"
                        >
                            Safety Center
                        </a>
                    </div>
                </div>

                <!-- Privacy & Security Notice -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-5 space-y-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-black text-slate-900">Your Privacy Matters</h3>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Your contact details and message content are protected with HTTPS/TLS in transit and AES-256 field encryption at rest. We never sell or share your information for marketing.
                    </p>
                    <div class="flex flex-wrap items-center gap-3 pt-1 text-xs font-bold">
                        <a href="{{ route('pages.privacy') }}" class="text-[#F20B65] hover:text-orange-600 transition-colors underline underline-offset-2">Privacy Policy</a>
                        <span class="text-slate-300">&bull;</span>
                        <a href="{{ route('pages.terms') }}" class="text-[#F20B65] hover:text-orange-600 transition-colors underline underline-offset-2">Terms of Service</a>
                    </div>
                </div>

            </aside>

            <!-- Right Section: Dynamic Helper Panel (Dating Tips / FAQs) + Support Ticket Form -->
            <main class="lg:col-span-8 space-y-6">

                <!-- Contextual Dating Tips Card (shown when Dating Tips category is active) -->
                <section
                    x-show="activeCategory === 'dating_tips'"
                    x-transition
                    x-cloak
                    class="bg-white rounded-2xl border border-orange-200 shadow-xs p-6 space-y-4"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-orange-600">Platform Guide</span>
                            <h2 class="text-lg font-black text-slate-900">Dating Tips &amp; Profile Best Practices</h2>
                        </div>
                        <button
                            type="button"
                            @click="activeCategory = 'contact_support'"
                            class="text-xs font-bold text-slate-500 hover:text-slate-800 px-2.5 py-1 rounded-lg hover:bg-slate-100 transition"
                        >
                            Dismiss
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                            <h3 class="text-xs font-black text-slate-900">Clear, Honest Profiles</h3>
                            <p class="text-xs text-slate-600 leading-relaxed">Write a genuine headline and bio with clear expectations to attract better local matches.</p>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                            <h3 class="text-xs font-black text-slate-900">Meet Safely First</h3>
                            <p class="text-xs text-slate-600 leading-relaxed">Keep early conversations on LocalDateHub and choose public venues for your first meetup.</p>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1.5">
                            <h3 class="text-xs font-black text-slate-900">Verify for Trust</h3>
                            <p class="text-xs text-slate-600 leading-relaxed">Verified profiles build confidence and receive stronger visibility across city searches.</p>
                        </div>
                    </div>
                </section>

                <!-- Contextual FAQs Accordion (shown when FAQs category is active) -->
                <section
                    x-show="activeCategory === 'faqs'"
                    x-transition
                    x-cloak
                    class="bg-white rounded-2xl border border-pink-200 shadow-xs p-6 space-y-4"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-[#F20B65]">Quick Answers</span>
                            <h2 class="text-lg font-black text-slate-900">Frequently Asked Questions</h2>
                        </div>
                        <button
                            type="button"
                            @click="activeCategory = 'contact_support'"
                            class="text-xs font-bold text-slate-500 hover:text-slate-800 px-2.5 py-1 rounded-lg hover:bg-slate-100 transition"
                        >
                            Dismiss
                        </button>
                    </div>
                    <div class="divide-y divide-slate-200 border border-slate-200 rounded-xl overflow-hidden">
                        <div>
                            <button
                                type="button"
                                @click="openFaq = openFaq === 0 ? null : 0"
                                class="w-full px-4 py-3.5 text-left flex items-center justify-between gap-3 bg-white hover:bg-slate-50 transition"
                            >
                                <span class="text-xs sm:text-sm font-bold text-slate-900">How quickly does LocalDateHub support respond?</span>
                                <span class="text-xs font-bold text-[#F20B65]" x-text="openFaq === 0 ? '−' : '+'"></span>
                            </button>
                            <div x-show="openFaq === 0" class="px-4 pb-3.5 text-xs text-slate-600 leading-relaxed bg-slate-50/60">
                                Safety and urgent abuse reports are prioritized under 1 hour. Billing questions are typically answered within 4–8 hours, and general inquiries within 24 hours.
                            </div>
                        </div>
                        <div>
                            <button
                                type="button"
                                @click="openFaq = openFaq === 1 ? null : 1"
                                class="w-full px-4 py-3.5 text-left flex items-center justify-between gap-3 bg-white hover:bg-slate-50 transition"
                            >
                                <span class="text-xs sm:text-sm font-bold text-slate-900">How do I report a suspicious user or fake listing?</span>
                                <span class="text-xs font-bold text-[#F20B65]" x-text="openFaq === 1 ? '−' : '+'"></span>
                            </button>
                            <div x-show="openFaq === 1" class="px-4 pb-3.5 text-xs text-slate-600 leading-relaxed bg-slate-50/60">
                                Choose <strong>Safety &amp; Reporting</strong> in the form below and include the listing URL or username so our Trust &amp; Safety team can investigate right away.
                            </div>
                        </div>
                        <div>
                            <button
                                type="button"
                                @click="openFaq = openFaq === 2 ? null : 2"
                                class="w-full px-4 py-3.5 text-left flex items-center justify-between gap-3 bg-white hover:bg-slate-50 transition"
                            >
                                <span class="text-xs sm:text-sm font-bold text-slate-900">Can I get help with wallet credits, boosts, or crypto invoices?</span>
                                <span class="text-xs font-bold text-[#F20B65]" x-text="openFaq === 2 ? '−' : '+'"></span>
                            </button>
                            <div x-show="openFaq === 2" class="px-4 pb-3.5 text-xs text-slate-600 leading-relaxed bg-slate-50/60">
                                Yes! Select <strong>Billing &amp; Payments</strong> below and include your invoice ID or transaction hash if available.
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Support Ticket Form Card -->
                <section id="support-ticket-form" class="relative overflow-hidden bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6 sm:p-8">
                    <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-[#F20B65] via-pink-500 to-orange-500"></div>

                    <!-- Form Header -->
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 pb-6 mb-6 border-b border-slate-200/80">
                        <div class="space-y-1">
                            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Send a Support Ticket</h2>
                            <p class="text-xs sm:text-sm text-slate-600">
                                Fill out the form below and our support team will get back to you as soon as possible.
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-bold shrink-0">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                            AES-256 Field Encrypted
                        </span>
                    </div>

                    <!-- Success Alert -->
                    @if (session('success'))
                        <div role="status" class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start gap-3">
                            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="text-sm font-bold text-emerald-900">Support Ticket Received</p>
                                <p class="text-xs text-emerald-800 mt-0.5">{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- Validation Summary Alert -->
                    @if ($errors->any())
                        <div role="alert" class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 flex items-start gap-3">
                            <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="text-sm font-bold text-rose-900">Please check the highlighted fields below.</p>
                                <ul class="mt-1 text-xs text-rose-800 list-disc list-inside space-y-0.5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <form
                        action="{{ route('pages.contact.submit') }}"
                        method="POST"
                        @submit="submitting = true"
                        class="space-y-5"
                    >
                        @csrf

                        <!-- Full Name & Email Address -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                                    Full Name <span class="text-[#F20B65]">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    value="{{ old('name', Auth::user()?->name) }}"
                                    placeholder="Your full name"
                                    required
                                    maxlength="100"
                                    class="w-full bg-slate-50/70 border @error('name') border-rose-500 @else border-slate-300 @enderror rounded-xl px-4 py-3 text-sm text-slate-900 font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-pink-500/15 focus:border-[#F20B65] transition"
                                />
                                @error('name')
                                    <p class="mt-1.5 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                                    Email Address <span class="text-[#F20B65]">*</span>
                                </label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email', Auth::user()?->email) }}"
                                    placeholder="you@domain.com"
                                    required
                                    maxlength="150"
                                    class="w-full bg-slate-50/70 border @error('email') border-rose-500 @else border-slate-300 @enderror rounded-xl px-4 py-3 text-sm text-slate-900 font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-pink-500/15 focus:border-[#F20B65] transition"
                                />
                                @error('email')
                                    <p class="mt-1.5 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Inquiry Topic Selector (7 Categories) -->
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Inquiry Topic <span class="text-[#F20B65]">*</span>
                                </span>
                                <span class="text-[11px] font-semibold text-slate-500">Choose one category</span>
                            </div>

                            <input type="hidden" name="inquiry_type" :value="selectedTopic" required>

                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-2.5">
                                @php
                                    $topics = [
                                        ['key' => 'account_profile', 'label' => 'Account & Profile', 'desc' => 'Access, settings & verification', 'badge' => null],
                                        ['key' => 'billing_payments', 'label' => 'Billing & Payments', 'desc' => 'Payments, boosts & wallet', 'badge' => null],
                                        ['key' => 'safety_reporting', 'label' => 'Safety & Reporting', 'desc' => 'Abuse, scams & fake profiles', 'badge' => 'Priority'],
                                        ['key' => 'dating_matching', 'label' => 'Dating & Matching', 'desc' => 'Messaging & profile tips', 'badge' => null],
                                        ['key' => 'technical_issues', 'label' => 'Technical Issues', 'desc' => 'Bugs, errors & performance', 'badge' => null],
                                        ['key' => 'partnership_business', 'label' => 'Partnership & Business', 'desc' => 'Media & collaborations', 'badge' => null],
                                        ['key' => 'other_inquiry', 'label' => 'Other Inquiry', 'desc' => 'General questions & feedback', 'badge' => null],
                                    ];
                                @endphp

                                @foreach ($topics as $topic)
                                    <button
                                        type="button"
                                        @click="selectedTopic = '{{ $topic['key'] }}'"
                                        :aria-pressed="selectedTopic === '{{ $topic['key'] }}'"
                                        :class="selectedTopic === '{{ $topic['key'] }}'
                                            ? 'border-[#F20B65] bg-pink-50/70 ring-2 ring-pink-500/15'
                                            : 'border-slate-200 bg-white hover:border-pink-300 hover:bg-slate-50/70'"
                                        class="p-3 rounded-xl border text-left transition-all flex items-start justify-between gap-2 focus:outline-none focus:ring-2 focus:ring-[#F20B65]"
                                    >
                                        <span class="min-w-0">
                                            <span class="flex items-center gap-1.5 flex-wrap">
                                                <span class="text-xs font-bold text-slate-900">{{ $topic['label'] }}</span>
                                                @if ($topic['badge'])
                                                    <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-rose-100 text-rose-700">{{ $topic['badge'] }}</span>
                                                @endif
                                            </span>
                                            <span class="block text-[11px] text-slate-500 mt-0.5 leading-snug">{{ $topic['desc'] }}</span>
                                        </span>
                                        <span
                                            class="w-4 h-4 rounded-full border flex items-center justify-center shrink-0 mt-0.5 transition-colors"
                                            :class="selectedTopic === '{{ $topic['key'] }}' ? 'border-[#F20B65] bg-[#F20B65] text-white' : 'border-slate-300 bg-white'"
                                        >
                                            <svg x-show="selectedTopic === '{{ $topic['key'] }}'" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                    </button>
                                @endforeach
                            </div>

                            @error('inquiry_type')
                                <p class="mt-1.5 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Subject & Optional Phone -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="subject" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                                    Subject <span class="text-[#F20B65]">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="subject"
                                    name="subject"
                                    value="{{ old('subject') }}"
                                    placeholder="Brief summary of your question or issue"
                                    required
                                    maxlength="150"
                                    class="w-full bg-slate-50/70 border @error('subject') border-rose-500 @else border-slate-300 @enderror rounded-xl px-4 py-3 text-sm text-slate-900 font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-pink-500/15 focus:border-[#F20B65] transition"
                                />
                                @error('subject')
                                    <p class="mt-1.5 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                                    Phone Number <span class="text-slate-400 font-semibold normal-case">(Optional)</span>
                                </label>
                                <input
                                    type="tel"
                                    id="phone"
                                    name="phone"
                                    value="{{ old('phone') }}"
                                    placeholder="+1 (555) 000-0000"
                                    maxlength="25"
                                    class="w-full bg-slate-50/70 border @error('phone') border-rose-500 @else border-slate-300 @enderror rounded-xl px-4 py-3 text-sm text-slate-900 font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-pink-500/15 focus:border-[#F20B65] transition"
                                />
                                @error('phone')
                                    <p class="mt-1.5 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Message Details -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label for="message" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Message Details <span class="text-[#F20B65]">*</span>
                                </label>
                                <span class="text-[11px] font-semibold text-slate-500">
                                    <span x-text="messageLength"></span> / 3000 (min 10 chars)
                                </span>
                            </div>
                            <textarea
                                id="message"
                                name="message"
                                rows="5"
                                required
                                minlength="10"
                                maxlength="3000"
                                @input="messageLength = $el.value.length"
                                placeholder="Please describe how we can help, including any relevant listing links, usernames, or invoice IDs..."
                                class="w-full bg-slate-50/70 border @error('message') border-rose-500 @else border-slate-300 @enderror rounded-xl px-4 py-3 text-sm text-slate-900 font-medium placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-pink-500/15 focus:border-[#F20B65] transition"
                            >{{ old('message') }}</textarea>
                            @error('message')
                                <p class="mt-1.5 text-xs text-rose-600 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Accurate Security Callout -->
                        <div class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200 flex items-start gap-3">
                            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <div class="text-xs text-emerald-950">
                                <strong class="font-bold text-emerald-900">Protected Submission:</strong>
                                Sent over HTTPS/TLS and stored with AES-256 field encryption so only authorized support specialists can review your ticket.
                            </div>
                        </div>

                        <!-- Turnstile Verification -->
                        <x-turnstile action="contact" class="pt-1" />

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="submitting"
                                :class="submitting ? 'opacity-75 cursor-not-allowed' : 'hover:opacity-95 active:scale-[0.99]'"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-7 py-3.5 rounded-xl bg-gradient-to-r from-[#F20B65] via-pink-500 to-orange-500 text-white text-sm font-bold shadow-md shadow-pink-500/20 transition-all cursor-pointer focus:outline-none focus:ring-4 focus:ring-pink-500/25"
                            >
                                <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="submitting ? 'Sending Support Ticket...' : 'Send Support Ticket'"></span>
                            </button>
                        </div>
                    </form>
                </section>

            </main>
        </div>
    </div>
</div>
@endsection
