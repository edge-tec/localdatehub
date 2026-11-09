<?php

namespace Database\Seeders;

use App\Modules\Admin\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'LocalDateHub', 'group' => 'general', 'type' => 'string', 'description' => 'Platform display name', 'is_public' => true],
            ['key' => 'tagline', 'value' => 'Safe & Private Local Adult Personal Classifieds', 'group' => 'general', 'type' => 'string', 'description' => 'Platform tagline', 'is_public' => true],
            ['key' => 'support_email', 'value' => 'support@localdatehub.com', 'group' => 'general', 'type' => 'string', 'description' => 'Public customer support email', 'is_public' => true],
            ['key' => 'support_phone', 'value' => '+1 (800) 555-DATE', 'group' => 'general', 'type' => 'string', 'description' => 'Customer support telephone', 'is_public' => true],
            ['key' => 'copyright_text', 'value' => '© 2026 LocalDateHub. All rights reserved. 18+ Adults Only.', 'group' => 'general', 'type' => 'string', 'description' => 'Footer copyright declaration', 'is_public' => true],
            ['key' => 'support_telegram', 'value' => '@LocalDateHubSupport', 'group' => 'general', 'type' => 'string', 'description' => 'VIP Telegram direct support handle', 'is_public' => true],

            // Branding & Logo
            ['key' => 'site_logo_url', 'value' => '/images/logo.png', 'group' => 'branding', 'type' => 'string', 'description' => 'Header logo (for light background)', 'is_public' => true],
            ['key' => 'site_logo_dark_url', 'value' => '/images/logo-white.png', 'group' => 'branding', 'type' => 'string', 'description' => 'Header logo (for dark background)', 'is_public' => true],
            ['key' => 'site_mobile_logo_url', 'value' => '/images/logo-mobile.png', 'group' => 'branding', 'type' => 'string', 'description' => 'Mobile compact logo / icon', 'is_public' => true],
            ['key' => 'site_favicon_url', 'value' => '/favicon.ico', 'group' => 'branding', 'type' => 'string', 'description' => 'Browser tab favicon URL (.ico or .png)', 'is_public' => true],
            ['key' => 'primary_brand_color', 'value' => '#F20B83', 'group' => 'branding', 'type' => 'string', 'description' => 'Primary theme brand accent color', 'is_public' => true],

            // SMTP & Mail Gateway
            ['key' => 'mail_mailer', 'value' => 'smtp', 'group' => 'smtp', 'type' => 'string', 'description' => 'Mail transport driver (smtp, sendmail, log)', 'is_public' => false],
            ['key' => 'mail_host', 'value' => 'smtp.mailgun.org', 'group' => 'smtp', 'type' => 'string', 'description' => 'SMTP relay server hostname', 'is_public' => false],
            ['key' => 'mail_port', 'value' => '587', 'group' => 'smtp', 'type' => 'integer', 'description' => 'SMTP server port (e.g. 587, 465, 2525)', 'is_public' => false],
            ['key' => 'mail_username', 'value' => 'postmaster@mg.localdatehub.com', 'group' => 'smtp', 'type' => 'string', 'description' => 'SMTP authentication username', 'is_public' => false],
            ['key' => 'mail_password', 'value' => '••••••••••••••••', 'group' => 'smtp', 'type' => 'string', 'description' => 'SMTP authentication password / token', 'is_public' => false],
            ['key' => 'mail_encryption', 'value' => 'tls', 'group' => 'smtp', 'type' => 'string', 'description' => 'Mail transport encryption (tls, ssl, none)', 'is_public' => false],
            ['key' => 'mail_from_address', 'value' => 'notifications@localdatehub.com', 'group' => 'smtp', 'type' => 'string', 'description' => 'Default system outgoing email address', 'is_public' => true],
            ['key' => 'mail_from_name', 'value' => 'LocalDateHub Notifications', 'group' => 'smtp', 'type' => 'string', 'description' => 'Default outgoing sender display name', 'is_public' => true],

            // Email Notification Event Switches
            ['key' => 'mail_account_verification_enabled', 'value' => 'true', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Require email verification link/OTP on account creation', 'is_public' => false],
            ['key' => 'mail_welcome_enabled', 'value' => 'true', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Send account created successfully welcome email', 'is_public' => false],
            ['key' => 'mail_account_approved_enabled', 'value' => 'true', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Send account approved by admin email', 'is_public' => false],
            ['key' => 'mail_account_verified_enabled', 'value' => 'true', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Send 18+ age & identity verified notification email', 'is_public' => false],
            ['key' => 'mail_password_reset_enabled', 'value' => 'true', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Send password reset OTP / link email', 'is_public' => false],
            ['key' => 'mail_post_pending_enabled', 'value' => 'true', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Send post submitted & pending review email', 'is_public' => false],
            ['key' => 'mail_post_published_enabled', 'value' => 'true', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Send post published live on site email', 'is_public' => false],
            ['key' => 'mail_post_rejected_enabled', 'value' => 'true', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Send post rejected with moderator reason email', 'is_public' => false],
            ['key' => 'mail_post_deleted_enabled', 'value' => 'true', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Send post deleted notification email', 'is_public' => false],
            ['key' => 'mail_post_expiring_soon_enabled', 'value' => 'true', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Send post expiring soon renew/boost reminder', 'is_public' => false],
            ['key' => 'mail_payment_pending_enabled', 'value' => 'true', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Send payment pending / invoice created email', 'is_public' => false],
            ['key' => 'mail_payment_received_enabled', 'value' => 'true', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Send blockchain payment detected email', 'is_public' => false],
            ['key' => 'mail_payment_success_enabled', 'value' => 'true', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Send payment confirmed & receipt email', 'is_public' => false],
            ['key' => 'mail_payment_declined_enabled', 'value' => 'true', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Send payment failed / underpaid alert email', 'is_public' => false],
            ['key' => 'mail_balance_added_enabled', 'value' => 'true', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Send credits added to wallet notification email', 'is_public' => false],
            ['key' => 'mail_low_balance_alert_enabled', 'value' => 'true', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Send low wallet credit balance warning email', 'is_public' => false],
            ['key' => 'low_balance_alert_threshold', 'value' => '5', 'group' => 'notifications', 'type' => 'integer', 'description' => 'Low credit balance trigger threshold', 'is_public' => false],
            ['key' => 'mail_new_message_alert_enabled', 'value' => 'true', 'group' => 'notifications', 'type' => 'boolean', 'description' => 'Send instant email alert when user receives a new message', 'is_public' => false],
            ['key' => 'mail_message_throttle_minutes', 'value' => '5', 'group' => 'notifications', 'type' => 'integer', 'description' => 'Throttle interval to prevent message alert spam (minutes)', 'is_public' => false],

            // Safety
            ['key' => 'min_age_requirement', 'value' => '18', 'group' => 'safety', 'type' => 'integer', 'description' => 'Minimum legal age for platform membership', 'is_public' => true],
            ['key' => 'anti_trafficking_enforcement', 'value' => 'true', 'group' => 'safety', 'type' => 'boolean', 'description' => 'Automated anti-trafficking keyword scanning', 'is_public' => false],
            ['key' => 'location_fuzzing_enabled', 'value' => 'true', 'group' => 'safety', 'type' => 'boolean', 'description' => 'Anti-triangulation random location offset', 'is_public' => false],
            ['key' => 'photo_exif_strip_enabled', 'value' => 'true', 'group' => 'safety', 'type' => 'boolean', 'description' => 'Strip camera metadata and GPS tags from photos', 'is_public' => false],
            ['key' => 'mandatory_age_verification_to_post', 'value' => 'true', 'group' => 'safety', 'type' => 'boolean', 'description' => 'Mandatory 18+ age assurance required before publishing ads', 'is_public' => false],
            ['key' => 'custodian_of_records_notice', 'value' => '18 U.S.C. 2257 Record-Keeping Compliance Notice Active. Records kept at Compliance Dept.', 'group' => 'safety', 'type' => 'string', 'description' => 'FOSTA-SESTA & 2257 custodian compliance statement', 'is_public' => true],

            // Monetization
            ['key' => 'free_listings_enabled', 'value' => 'true', 'group' => 'monetization', 'type' => 'boolean', 'description' => 'Allow free standard personal classifieds', 'is_public' => true],
            ['key' => 'max_photos_per_listing', 'value' => '6', 'group' => 'monetization', 'type' => 'integer', 'description' => 'Maximum allowed photos per personal ad', 'is_public' => true],
            ['key' => 'listing_active_days', 'value' => '30', 'group' => 'monetization', 'type' => 'integer', 'description' => 'Days before listing expires and requires renewal', 'is_public' => true],
            ['key' => 'max_free_listings_per_user', 'value' => '3', 'group' => 'monetization', 'type' => 'integer', 'description' => 'Maximum active free ads per regular member', 'is_public' => true],

            // Locations
            ['key' => 'default_search_radius_miles', 'value' => '25', 'group' => 'locations', 'type' => 'integer', 'description' => 'Default radius for nearby search', 'is_public' => true],
            ['key' => 'distance_unit', 'value' => 'miles', 'group' => 'locations', 'type' => 'string', 'description' => 'Distance calculation unit (miles or km)', 'is_public' => true],
            ['key' => 'max_search_results', 'value' => '100', 'group' => 'locations', 'type' => 'integer', 'description' => 'Maximum listings returned per search query', 'is_public' => true],

            // Security & Operations
            ['key' => 'maintenance_mode_enabled', 'value' => 'false', 'group' => 'security', 'type' => 'boolean', 'description' => 'Take platform into maintenance mode', 'is_public' => true],
            ['key' => 'maintenance_announcement_message', 'value' => 'LocalDateHub is undergoing scheduled infrastructure maintenance. We will be back online shortly.', 'group' => 'security', 'type' => 'string', 'description' => 'Message displayed to visitors during maintenance', 'is_public' => true],
            ['key' => 'payment_gateway_mode', 'value' => 'test', 'group' => 'payment', 'type' => 'string', 'description' => 'Payment gateway operating mode (test or live)', 'is_public' => false],
            ['key' => 'payment_gateway_provider', 'value' => 'stripe', 'group' => 'payment', 'type' => 'string', 'description' => 'Active payment processor identifier', 'is_public' => false],
            ['key' => 'admin_2fa_required', 'value' => 'true', 'group' => 'security', 'type' => 'boolean', 'description' => 'Require two-factor authentication for staff accounts', 'is_public' => false],
            ['key' => 'admin_session_timeout_minutes', 'value' => '30', 'group' => 'security', 'type' => 'integer', 'description' => 'Idle timeout for administrative sessions', 'is_public' => false],
            ['key' => 'max_login_attempts', 'value' => '5', 'group' => 'security', 'type' => 'integer', 'description' => 'Maximum login attempts before temporary IP lock', 'is_public' => false],
        ];

        foreach ($settings as $s) {
            SystemSetting::firstOrCreate(['key' => $s['key']], $s);
        }
    }
}
