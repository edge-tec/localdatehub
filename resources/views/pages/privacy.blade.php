<x-layouts.app title="Privacy Policy — LocalDateHub">
    <div class="py-12 bg-slate-950">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-breadcrumbs :items="[
                ['label' => 'Home', 'url' => route('home')],
                ['label' => 'Privacy Policy']
            ]" />

            <div class="mt-8">
                <x-badge variant="rose">Legal & Data Protection</x-badge>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-white mt-3 tracking-tight">
                    Privacy Policy & Data Security
                </h1>
                <p class="mt-2 text-xs text-slate-400">
                    Last Updated: November 2026 • Effective Date: November 1, 2026
                </p>
                <p class="mt-4 text-base text-slate-300 leading-relaxed">
                    At LocalDateHub, your privacy is fundamental to our architecture. We do not sell your personal data, track you across the web, or monetize through intrusive advertising brokers.
                </p>
            </div>

            <div class="mt-10 space-y-8">
                <x-card>
                    <h2 class="text-lg font-bold text-white mb-2">1. Information We Collect</h2>
                    <p class="text-sm text-slate-300 leading-relaxed mb-3">
                        We collect only the minimum data required to facilitate safe classified connections:
                    </p>
                    <ul class="space-y-2 text-sm text-slate-300 list-disc list-inside">
                        <li><strong>Account Credentials:</strong> Name or handle, email address, and hashed passwords (Argon2id/Bcrypt).</li>
                        <li><strong>Listing Content:</strong> Ad titles, descriptions, dynamic attributes (e.g. intent, age, interests), and approved uploaded imagery.</li>
                        <li><strong>Relayed Communications:</strong> Inquiries sent through listing contact forms are stored with AES-256 field encryption.</li>
                        <li><strong>Technical Telemetry:</strong> Anonymized server logs, coarse approximate city geolocation, and session tokens.</li>
                    </ul>
                </x-card>

                <x-card>
                    <h2 class="text-lg font-bold text-white mb-2">2. Geolocation Privacy & Anti-Triangulation</h2>
                    <p class="text-sm text-slate-300 leading-relaxed">
                        To protect your physical safety and prevent stalking or precise residence tracking:
                    </p>
                    <ul class="mt-3 space-y-2 text-sm text-slate-300 list-disc list-inside">
                        <li>We never display precise GPS coordinates or exact street addresses publicly.</li>
                        <li>All public map representations and distance calculations apply an automated anti-triangulation fuzzing jitter of 300 to 800 meters.</li>
                        <li>Distance badges are grouped into coarse ranges (e.g. "Within 10 km") rather than exact distance metrics.</li>
                    </ul>
                </x-card>

                <x-card>
                    <h2 class="text-lg font-bold text-white mb-2">3. Media Sanitization & EXIF Data</h2>
                    <p class="text-sm text-slate-300 leading-relaxed">
                        Whenever you upload photos to LocalDateHub, our automated image pipeline processes every file before storage:
                    </p>
                    <ul class="mt-3 space-y-2 text-sm text-slate-300 list-disc list-inside">
                        <li>All EXIF metadata (including camera manufacturer, serial number, GPS coordinates, altitude, and timestamp) is completely stripped.</li>
                        <li>Images are re-encoded to modern WebP format with automated compression and resolution normalization.</li>
                    </ul>
                </x-card>

                <x-card>
                    <h2 class="text-lg font-bold text-white mb-2">4. Your Rights: CCPA, CPRA & GDPR</h2>
                    <p class="text-sm text-slate-300 leading-relaxed">
                        Regardless of your jurisdiction, you have full ownership of your data:
                    </p>
                    <ul class="mt-3 space-y-2 text-sm text-slate-300 list-disc list-inside">
                        <li><strong>Right of Access & Portability:</strong> View and export your listings and profile at any time.</li>
                        <li><strong>Right to Deletion:</strong> You can permanently delete your listings and your user account directly from your account settings at any time without having to contact support.</li>
                        <li><strong>No Sale of Personal Data:</strong> We do not sell or rent personal information to third parties under any circumstances.</li>
                    </ul>
                </x-card>

                <x-card>
                    <h2 class="text-lg font-bold text-white mb-2">5. Contact Our Privacy Officer</h2>
                    <p class="text-sm text-slate-300 leading-relaxed">
                        For questions regarding privacy practices, submit a ticket via our <a href="{{ route('pages.contact') }}" class="text-rose-400 underline hover:text-rose-300">Contact Center</a> or email <code class="text-xs text-rose-300 bg-slate-900 px-2 py-0.5 rounded">privacy@localdatehub.com</code>.
                    </p>
                </x-card>
            </div>
        </div>
    </div>
</x-layouts.app>

