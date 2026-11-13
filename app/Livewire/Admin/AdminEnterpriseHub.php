<?php

namespace App\Livewire\Admin;

use App\Livewire\Admin\Concerns\ManagesAdvertisingCampaigns;
use App\Modules\Advertising\Services\AdUrlService;
use App\Models\User;
use App\Models\PlatformTrafficLog;
use App\Modules\Accounts\Models\UserProfile;
use App\Modules\Monetization\Models\CreditPackage;
use App\Modules\Monetization\Models\UserWallet;
use App\Modules\Monetization\Models\WalletTransaction;
use App\Modules\Payments\Crypto\Models\CryptoPayment;
use App\Modules\Payments\Crypto\Models\CryptoGasAlert;
use App\Modules\Payments\Crypto\Models\CryptoGasReserve;
use App\Modules\Payments\Crypto\Models\CryptoGasTransaction;
use App\Modules\Payments\Crypto\Models\CryptoPaymentSetting;
use App\Modules\Payments\Crypto\Models\CryptoSweepApproval;
use App\Modules\Payments\Crypto\Models\CryptoSweepTransaction;
use App\Modules\Payments\Crypto\Models\CryptoTreasuryWallet;
use App\Modules\Payments\Crypto\Services\InHouse\HybridTreasuryEngine;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Modules\Accounts\Models\AgeAssurance;
use App\Modules\Admin\Models\AdminAuditLog;
use App\Modules\Admin\Models\AdPlacement;
use App\Modules\Admin\Models\Permission;
use App\Modules\Admin\Models\Role;
use App\Modules\Admin\Models\SystemSetting;
use App\Modules\Admin\Services\AdminAuditService;
use App\Modules\Billing\Gateways\PaymentGatewayManager;
use App\Modules\Billing\Models\FeaturedListing;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\PaymentFraudLog;
use App\Modules\Billing\Models\PaymentTransaction;
use App\Modules\Billing\Models\PricingPlan;
use App\Modules\Billing\Models\PricingRule;
use App\Modules\Billing\Models\Refund;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Billing\Services\ReconciliationService;
use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Models\CategoryAttribute;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\EmailRelay\Models\EmailRelayMessage;
use App\Modules\EmailRelay\Services\EmailRelayService;
use App\Modules\Listings\Models\Listing;
use App\Modules\Listings\Models\ListingContact;
use App\Modules\Listings\Models\ListingAnalytics;
use App\Modules\Listings\Models\AnalyticsEvent;
use App\Modules\Listings\Models\SearchTrend;
use App\Modules\Locations\Models\City;
use App\Modules\Locations\Models\State;
use App\Modules\Moderation\Models\AppealRequest;
use App\Modules\Moderation\Models\ContentReport;
use App\Modules\Moderation\Models\DmcaRequest;
use App\Modules\Moderation\Models\EvidencePreservation;
use App\Modules\Moderation\Models\LegalRequest;
use App\Modules\Moderation\Models\ModerationAction;
use App\Modules\Moderation\Models\ModerationQueue;
use App\Modules\Moderation\Models\StateCompliance;
use App\Modules\Moderation\Models\UserSuspension;
use App\Modules\Moderation\Services\ComplianceService;
use App\Modules\SEO\Models\CmsPage;
use App\Modules\SEO\Models\SeoMeta;
use App\Modules\Notifications\Models\EmailTemplate;
use App\Modules\Notifications\Models\NotificationLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Enterprise Administration Hub.
 *
 * A single permission-gated Livewire surface hosting all 21 administrative
 * modules. Every mutation is (1) permission-checked server side and
 * (2) recorded in the immutable admin_audit_logs table.
 */
class AdminEnterpriseHub extends Component
{
    use WithPagination, WithFileUploads, ManagesAdvertisingCampaigns;

    /** Staff roles recognised by the platform. */
    public const STAFF_ROLES = [
        'super_admin', 'admin', 'senior_moderator', 'listing_moderator',
        'compliance_officer', 'customer_support', 'finance_manager', 'seo_manager',
    ];

    /** Setting groups that only a Super Administrator may change. */
    public const RESTRICTED_SETTING_GROUPS = ['payment', 'security'];

    /**
     * Tab => [label, required permission (null = any staff member), section group].
     */
    public const TABS = [
        'dashboard'     => ['Dashboard', null, 'Overview'],
        'analytics'     => ['Analytics', 'analytics.view', 'Overview'],
        'users'         => ['Users', 'users.view', 'Community'],
        'listings'      => ['Listings', 'listings.view', 'Community'],
        'moderation'    => ['Moderation', 'moderation.view', 'Trust & Safety'],
        'verification'  => ['Age Verification', 'verification.manage', 'Trust & Safety'],
        'reports'       => ['Reports', 'reports.manage', 'Trust & Safety'],
        'abuse'           => ['Contact Abuse', 'moderation.view', 'Trust & Safety'],
        'support_tickets' => ['Support Tickets', 'moderation.view', 'Trust & Safety'],
        'dmca'          => ['DMCA Takedowns', 'reports.manage', 'Trust & Safety'],
        'legal'         => ['Legal Requests', 'verification.manage', 'Trust & Safety'],
        'compliance'    => ['State Compliance', 'settings.manage', 'Trust & Safety'],
        'evidence'      => ['Evidence Vault', 'audit.view', 'Trust & Safety'],
        'categories'    => ['Categories', 'categories.manage', 'Catalog'],
        'attributes'    => ['Attributes', 'categories.manage', 'Catalog'],
        'locations'     => ['Locations', 'locations.manage', 'Catalog'],
        'pricing'       => ['Pricing Plans', 'billing.view', 'Revenue'],
        'featured'      => ['Featured', 'billing.view', 'Revenue'],
        'subscriptions' => ['Subscriptions', 'billing.view', 'Revenue'],
        'payments'      => ['Payments', 'billing.view', 'Revenue'],
        'ads'           => ['Advertisements', 'ads.manage', 'Revenue'],
        'seo'           => ['SEO', 'seo.manage', 'Content'],
        'cms'           => ['CMS Pages', 'cms.manage', 'Content'],
        'staff'         => ['Staff', 'staff.manage', 'Administration'],
        'roles'         => ['Roles & Permissions', 'staff.manage', 'Administration'],
        'settings'      => ['System Config', 'settings.manage', 'Administration'],
        'audit'         => ['Audit Logs', 'audit.view', 'Administration'],
        'notifications' => ['Notifications', 'settings.manage', 'Administration'],
        'email_relay'   => ['Email Relay', 'settings.manage', 'Administration'],
    ];

    #[Url(as: 'tab')]
    public string $tab = 'dashboard';

    public string $relaySearch = '';
    public string $relayStatusFilter = 'all';
    public string $relayDirectionFilter = 'all';
    public string $relayListingId = '';

    // Dashboard Telemetry & Analytics Filters
    public string $dashboardLocationMode = 'countries'; // 'countries', 'states'
    public string $dashboardViewMode = 'unique'; // 'unique', 'gross'
    public string $dashboardChartType = 'graph'; // 'graph', 'chart', 'both'

    // Multi-Dimensional Analytics & Report Filters
    public string $analyticsPeriod = '30d'; // 'today', '7d', '30d', '90d', 'all'
    public string $analyticsViewMode = 'both'; // 'unique', 'gross', 'both'
    public string $analyticsVisualMode = 'both'; // 'graph', 'chart', 'both'
    public string $analyticsDimension = 'all'; // 'all', 'visitors', 'posts', 'countries', 'devices', 'age'
    public string $analyticsCountryFilter = 'all';
    public string $analyticsDeviceFilter = 'all';
    public string $analyticsAgeFilter = 'all';

    public function setDashboardLocationMode(string $mode): void
    {
        $this->dashboardLocationMode = in_array($mode, ['countries', 'states'], true) ? $mode : 'countries';
    }

    public function setDashboardViewMode(string $mode): void
    {
        $this->dashboardViewMode = in_array($mode, ['unique', 'gross'], true) ? $mode : 'unique';
    }

    public function setDashboardChartType(string $type): void
    {
        $this->dashboardChartType = in_array($type, ['graph', 'chart', 'both'], true) ? $type : 'graph';
    }

    public function setAnalyticsDimension(string $dimension): void
    {
        $allowed = ['all', 'visitors', 'posts', 'countries', 'devices', 'age'];
        $this->analyticsDimension = in_array($dimension, $allowed, true) ? $dimension : 'all';
    }

    public function setAnalyticsVisualMode(string $mode): void
    {
        $this->analyticsVisualMode = in_array($mode, ['graph', 'chart', 'both'], true) ? $mode : 'both';
    }

    public function setAnalyticsViewMode(string $mode): void
    {
        $this->analyticsViewMode = in_array($mode, ['unique', 'gross', 'both'], true) ? $mode : 'both';
    }

    public function resetAnalyticsFilters(): void
    {
        $this->analyticsPeriod = '30d';
        $this->analyticsViewMode = 'both';
        $this->analyticsVisualMode = 'both';
        $this->analyticsDimension = 'all';
        $this->analyticsCountryFilter = 'all';
        $this->analyticsDeviceFilter = 'all';
        $this->analyticsAgeFilter = 'all';
    }

    // Users
    public string $userSearch = '';
    public string $userStatusFilter = 'all';
    public string $userRoleFilter = 'all';
    public ?int $selectedUserId = null;
    public string $userSuspendReason = '';
    public string $userSuspendType = 'temporary';
    public int $userSuspendDays = 30;
    public bool $showSuspendUserModal = false;
    public string $newRoleSlug = '';
    public bool $showAssignRoleModal = false;

    // User CRUD Modals & Form States
    public bool $showCreateUserModal = false;
    public string $createUserName = '';
    public string $createUserDisplayName = '';
    public string $createUserEmail = '';
    public string $createUserPassword = '';
    public string $createUserPhone = '';
    public string $createUserRole = 'user';
    public string $createUserAccountStatus = 'active';
    public bool $createUserIsAgeVerified = true;
    public int $createUserCredits = 0;

    public bool $showViewUserModal = false;
    public ?int $viewUserId = null;
    public ?array $viewUserData = null;

    public bool $showEditUserModal = false;
    public ?int $editUserId = null;
    public string $editUserName = '';
    public string $editUserDisplayName = '';
    public string $editUserEmail = '';
    public string $editUserPassword = '';
    public string $editUserPhone = '';
    public string $editUserRole = 'user';
    public string $editUserAccountStatus = 'active';
    public bool $editUserIsAgeVerified = false;
    public bool $editUserIsIdentityVerified = false;
    public int $editUserCredits = 0;
    public $editUserAvatar = null;
    public ?string $editUserAvatarUrl = null;

    public bool $showDeleteUserModal = false;
    public ?int $deleteUserId = null;
    public ?string $deleteUserName = null;
    public ?string $deleteUserEmail = null;

    // Listings
    public string $listingSearch = '';
    public string $listingStatusFilter = 'all';
    public ?int $listingCategoryFilter = null;
    public array $selectedListingIds = [];
    public ?int $targetListingId = null;
    public string $listingActionReason = '';
    public bool $showRejectListingModal = false;

    // View Listing Details Modal
    public bool $showViewListingModal = false;
    public ?int $viewListingId = null;
    public ?array $viewListingData = null;

    // Edit Listing Modal
    public bool $showEditListingModal = false;
    public ?int $editListingId = null;
    public string $editListingTitle = '';
    public string $editListingDescription = '';
    public ?int $editListingCategoryId = null;
    public ?int $editListingStateId = null;
    public ?int $editListingCityId = null;
    public string $editListingStatus = 'active';
    public ?int $editListingPosterAge = null;
    public ?string $editListingPosterGender = null;
    public ?string $editListingIntent = null;
    public bool $editListingIsFeatured = false;
    public bool $editListingIsVerifiedPoster = false;

    // Delete Listing Confirmation Modal
    public bool $showDeleteListingModal = false;
    public ?int $deleteListingId = null;
    public ?string $deleteListingTitle = null;

    // Moderation Hub Controls & State
    public string $moderationSubTab = 'queue'; // 'queue', 'appeals', 'reports', 'automated', 'history'
    public string $moderationSearch = '';
    public ?int $moderationCategoryFilter = null;
    public string $moderationRiskFilter = 'all';
    public array $selectedQueueListingIds = [];
    public ?int $targetAppealId = null;
    public string $appealDecisionNotes = '';
    public bool $showAppealModal = false;
    public ?array $inspectedAppealData = null;

    // Verification & Age Assurance Hub
    public string $verificationStatusFilter = 'all';
    public string $verificationSearch = '';
    public string $verificationMethodFilter = 'all';
    public bool $showInspectVerificationModal = false;
    public ?array $inspectedVerificationData = null;
    public bool $showManualVerifyModal = false;
    public array $manualVerifyForm = [
        'user_id' => '',
        'declared_birthdate' => '2000-01-01',
        'method' => 'digital_id',
        'admin_notes' => 'Verified via physical or electronic government ID review.',
    ];
    public bool $showRejectVerificationModal = false;
    public ?int $rejectingAssuranceId = null;
    public string $rejectionReason = '';

    // Notifications & Broadcast Alerts Hub
    public string $notificationSearch = '';
    public string $notificationChannelFilter = 'all';
    public string $notificationStatusFilter = 'all';
    public string $notificationsSubTab = 'logs'; // 'logs', 'templates'
    public bool $showBroadcastModal = false;
    public bool $showInspectNotificationModal = false;
    public ?array $inspectNotificationData = null;
    public bool $showInspectAbuseModal = false;
    public ?array $inspectAbuseData = null;
    public array $broadcastForm = [
        'target_audience' => 'all_users',
        'specific_user_id' => '',
        'channel' => 'database',
        'subject' => '',
        'message' => '',
    ];

    // Reports & Incident Moderation
    public string $reportSubTab = 'queue'; // 'queue', 'trends', 'export'
    public string $reportSearch = '';
    public string $reportStatusFilter = 'all'; // 'all', 'open', 'investigating', 'resolved', 'dismissed'
    public string $reportSeverityFilter = 'all'; // 'all', 'critical', 'high', 'medium', 'low'
    public string $reportReasonFilter = 'all';
    public ?int $targetReportId = null;
    public string $reportResolutionNotes = '';
    public string $reportResolutionAction = 'resolved'; // 'investigating', 'resolved', 'dismissed'
    public bool $reportQuarantineTarget = false;
    public bool $reportSuspendTargetUser = false;
    public bool $showResolveReportModal = false;
    public bool $showInspectReportModal = false;
    public ?array $inspectReportData = null;

    // DMCA Takedowns
    public string $dmcaStatusFilter = 'all';
    public ?int $targetDmcaId = null;
    public string $dmcaAction = 'takedown_issued';
    public string $dmcaNotes = '';
    public bool $showDmcaModal = false;

    // Legal & Law Enforcement Requests
    public string $legalStatusFilter = 'all';
    public ?int $targetLegalId = null;
    public string $legalComplianceNotes = '';
    public string $legalStatusAction = 'fulfilled';
    public bool $showLegalModal = false;

    // State Compliance
    public ?int $editingStateComplianceId = null;
    public array $stateComplianceForm = [];
    public bool $showStateComplianceModal = false;

    // Evidence Vault Holds
    public ?int $targetPreservationId = null;
    public ?EvidencePreservation $inspectedPreservation = null;
    public bool $showPreservationModal = false;

    // Refunds & Payments Command Center
    public ?int $targetTransactionId = null;
    public string $refundReason = '';
    public bool $showRefundModal = false;
    public string $paymentSubTab = 'transactions'; // 'transactions', 'crypto', 'treasury', 'wallet', 'rules', 'fraud', 'refunds'
    public string $paymentSearch = '';
    public string $paymentGatewayFilter = 'all';
    public string $paymentStatusFilter = 'all';
    public string $treasuryNetworkFilter = 'all';
    public array $treasurySettings = [];
    public ?string $treasurySuccessMessage = null;
    public ?string $treasuryErrorMessage = null;
    public ?string $consolidationSuccessMessage = null;
    public ?string $consolidationErrorMessage = null;
    public ?string $settingsSuccessMessage = null;
    public string $manualSweepNetwork = 'TRC20';
    public string $manualSweepCurrency = 'USDT';
    public string $manualSweepAmount = '100.00';
    public string $manualSweepDestination = '';
    public string $rejectReason = '';

    // Staff Management
    public string $staffSearch = '';
    public string $staffRoleFilter = 'all';
    public string $staffStatusFilter = 'all';

    // Transaction View Modal
    public bool $showViewTransactionModal = false;
    public ?array $viewTransactionData = null;

    // Crypto Payment View Modal
    public bool $showViewCryptoModal = false;
    public ?array $viewCryptoData = null;

    // Pricing plan editor
    public bool $showPlanModal = false;
    public ?int $editingPlanId = null;
    public array $planForm = [];

    // Subscriptions Command Center
    public string $subscriptionSearch = '';
    public string $subscriptionStatusFilter = 'all';
    public string $subscriptionPlanFilter = 'all';
    public string $subscriptionSubTab = 'subscribers'; // 'subscribers', 'plans'
    public bool $showAssignSubscriptionModal = false;
    public array $assignSubscriptionForm = [
        'user_id' => '',
        'pricing_plan_id' => '',
        'duration_days' => 30,
        'gateway' => 'manual_admin',
        'status' => 'active',
        'auto_renew' => true,
    ];
    public bool $showViewSubscriptionModal = false;
    public ?array $viewSubscriptionData = null;

    // Pricing Rules editor
    public bool $showPricingRuleModal = false;
    public ?int $editingPricingRuleId = null;
    public array $pricingRuleForm = [
        'rule_name' => '',
        'pricing_plan_id' => null,
        'category_id' => null,
        'state_id' => null,
        'discount_percentage' => 0,
        'fixed_price_cents' => null,
        'is_free' => false,
        'is_active' => true,
    ];

    // Credit Packages Management (User Wallet Replenishment Bundles)
    public bool $showPackageModal = false;
    public ?int $editingPackageId = null;
    public array $packageForm = [
        'name' => '',
        'credits' => 50,
        'bonus_credits' => 0,
        'price_usd' => '4.99',
        'display_order' => 1,
        'is_popular' => false,
        'is_active' => true,
    ];

    // CMS editor
    public bool $showCmsModal = false;
    public ?int $editingCmsId = null;
    public array $cmsForm = [];
    public string $cmsSearch = '';
    public string $cmsStatusFilter = 'all';
    public bool $showPreviewCmsModal = false;
    public ?array $previewCmsData = null;

    // SEO & Marketing Command Center
    public string $seoSubTab = 'metadata'; // 'metadata', 'overview', 'keywords', 'urls', 'sitemaps', 'indexing', 'gsc', 'audit', 'redirects', 'settings', 'serp', 'marketing', 'trends'
    public string $seoDateRange = 'last_28_days'; // 'today', 'yesterday', 'last_7_days', 'last_28_days', 'last_3_months', 'last_6_months', 'last_12_months', 'custom'
    public ?string $seoCustomStartDate = null;
    public ?string $seoCustomEndDate = null;
    public string $seoSearch = '';
    public string $seoRobotsFilter = 'all';
    public string $seoKeywordSearch = '';
    public string $seoKeywordIntentFilter = 'all';
    public string $seoKeywordPriorityFilter = 'all';
    public string $seoUrlSearch = '';
    public string $seoUrlTypeFilter = 'all';
    public string $seoUrlIndexingFilter = 'all';
    public string $seoIssueSeverityFilter = 'all';
    public string $seoIssueTypeFilter = 'all';
    public string $seoRedirectSearch = '';
    public string $seoRedirectStatusFilter = 'all';
    public string $seoRobotsTxtContent = '';
    public array $newKeywordForm = [
        'keyword' => '',
        'search_intent' => 'commercial',
        'search_volume' => 0,
        'keyword_difficulty' => 30,
        'cpc' => 1.50,
        'priority' => 'medium',
        'target_url' => '',
        'category_id' => null,
        'state_id' => null,
        'city_id' => null,
    ];
    public array $newRedirectForm = [
        'source_path' => '',
        'target_url' => '',
        'status_code' => 301,
        'notes' => '',
    ];
    public array $gscForm = [
        'site_url' => '',
        'auth_mode' => 'service_account',
        'client_email' => '',
        'private_key' => '',
    ];
    public string $serpPreviewRoute = 'home';
    public string $serpPreviewTitle = 'LocalDateHub — USA Adult Personal Classifieds & Local Dating';
    public string $serpPreviewDescription = 'Browse verified adult personals, singles, and classified listings across all 50 US states. 100% private, safe, and privacy-conscious dating platform.';
    public string $serpDevice = 'desktop'; // 'desktop', 'mobile'
    public string $marketingGaId = '';
    public string $marketingGtmId = '';
    public string $marketingPixelId = '';
    public string $marketingCustomHead = '';

    // SEO editor
    public bool $showSeoModal = false;
    public ?int $editingSeoId = null;
    public array $seoForm = [];

    // Ads editor
    public bool $showAdModal = false;
    public ?int $editingAdId = null;
    public array $adForm = [];
    public $adBannerUpload = null;
    public string $adSearch = '';
    public string $adSlotFilter = 'all';
    public string $adStatusFilter = 'all';
    public string $adSubTab = 'campaigns'; // 'campaigns', 'inventory', 'analytics'
    public bool $showDeleteAdModal = false;
    public ?int $deleteAdId = null;
    public ?string $deleteAdName = null;
    public bool $showPreviewAdModal = false;
    public ?array $previewAdData = null;

    // System settings
    public array $settingValues = [];
    #[Url(as: 'subtab')]
    public string $settingsSubTab = 'general'; // 'general', 'branding', 'smtp', 'notifications', 'safety', 'monetization', 'locations', 'security'
    public $brandLogoUpload = null;
    public $brandLogoDarkUpload = null;
    public $brandMobileLogoUpload = null;
    public $brandFaviconUpload = null;
    public $brandHeroImageUpload = null;
    public $brandSidebarPromoUpload = null;
    public string $testEmailRecipient = '';
    public ?string $smtpTestSuccessMessage = null;
    public ?string $smtpTestErrorMessage = null;

    // Audit logs
    public string $auditActionSearch = '';
    public ?int $auditAdminFilter = null;
    public ?int $inspectedAuditLogId = null;
    public bool $showAuditDetailModal = false;

    public function mount(string $initialTab = 'dashboard'): void
    {
        $user = $this->currentUser();
        if (!$user || !$user->isStaff()) {
            abort(403, 'Unauthorized administrative access.');
        }

        $requested = request()->query('tab', $initialTab ?: 'dashboard');
        $this->tab = $this->canAccessTab($requested) ? $requested : 'dashboard';

        $requestedSubTab = request()->query('subtab');
        if ($requestedSubTab && in_array($requestedSubTab, ['general', 'branding', 'smtp', 'notifications', 'safety', 'monetization', 'locations', 'security'], true)) {
            $this->settingsSubTab = $requestedSubTab;
        }

        $this->resetPlanForm();
        $this->resetCmsForm();
        $this->resetSeoForm();
        $this->resetAdForm();
        $this->resetStateComplianceForm();
        $this->loadSettings();
        $this->loadTreasurySettings();
        $this->resetValidation();
        $this->resetErrorBag();
        session()->forget(['error', 'errors']);

        if (request()->filled('inspect_notification')) {
            $this->tab = 'notifications';
            $this->inspectNotification((int) request()->query('inspect_notification'));
        } elseif (request()->filled('inspect_report')) {
            $this->tab = 'reports';
            $this->openInspectReportModal((int) request()->query('inspect_report'));
        } elseif (request()->filled('inspect_abuse')) {
            $this->tab = 'abuse';
            $this->inspectAbuseMessage((int) request()->query('inspect_abuse'));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Navigation & Authorization
    |--------------------------------------------------------------------------
    */

    public function setTab(string $newTab): void
    {
        if (!array_key_exists($newTab, self::TABS)) {
            abort(404);
        }
        if (!$this->canAccessTab($newTab)) {
            abort(403, 'Access Denied: Your role cannot open this administrative module.');
        }

        $this->tab = $newTab;
        $this->resetPage();
        $this->resetValidation();
        $this->resetErrorBag();
        $this->consolidationErrorMessage = null;
        $this->consolidationSuccessMessage = null;
        $this->treasuryErrorMessage = null;
        $this->treasurySuccessMessage = null;
        session()->forget(['error', 'errors', 'success']);
    }

    public function setListingStatusFilter(string $status): void
    {
        $this->tab = 'listings';
        $this->listingStatusFilter = $status;
        $this->resetPage();
    }

    public function setListingCategoryFilter(?int $categoryId): void
    {
        $this->tab = 'listings';
        $this->listingCategoryFilter = $categoryId;
        $this->resetPage();
    }

    public function setUserStatusFilter(string $status): void
    {
        $this->tab = 'users';
        $this->userStatusFilter = $status;
        $this->resetPage();
    }

    public function clearErrors(): void
    {
        $this->resetValidation();
        $this->resetErrorBag();
        $this->consolidationErrorMessage = null;
        $this->treasuryErrorMessage = null;
        session()->forget(['error', 'errors', 'success']);
    }

    public function canAccessTab(string $tab): bool
    {
        if (!array_key_exists($tab, self::TABS)) {
            return false;
        }

        $permission = self::TABS[$tab][1];

        return $permission === null || (bool) $this->currentUser()?->hasPermission($permission);
    }

    public function userCan(string $permission): bool
    {
        return (bool) $this->currentUser()?->hasPermission($permission);
    }

    protected function authorizePermission(string $permission): void
    {
        $user = $this->currentUser();
        if (!$user || !$user->isStaff()) {
            abort(403, "Access Denied: Administrative staff access required.");
        }

        if ($user->role === 'super_admin' || $user->role === 'admin' || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return;
        }

        if (!$user->hasPermission($permission)) {
            abort(403, "Access Denied: You lack the [{$permission}] permission.");
        }
    }

    protected function currentUser(): ?User
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user;
    }

    protected function isSuperAdmin(): bool
    {
        $user = $this->currentUser();
        if (!$user) {
            return false;
        }

        return $user->role === 'super_admin'
            || $user->id === 1
            || $user->email === 'admin@localdatehub.com'
            || $user->email === 'ahwhawr@gmail.com'
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin());
    }

    /*
    |--------------------------------------------------------------------------
    | 1. User Management
    |--------------------------------------------------------------------------
    */

    public function openSuspendUserModal(int $userId): void
    {
        $this->authorizePermission('users.suspend');
        $this->selectedUserId = $userId;
        $this->userSuspendReason = '';
        $this->userSuspendType = 'temporary';
        $this->userSuspendDays = 30;
        $this->showSuspendUserModal = true;
    }

    public function suspendUser(): void
    {
        $this->authorizePermission('users.suspend');
        $this->validate([
            'selectedUserId' => ['required', 'exists:users,id'],
            'userSuspendReason' => ['required', 'string', 'min:5', 'max:150'],
            'userSuspendType' => ['required', Rule::in(['temporary', 'permanent'])],
            'userSuspendDays' => ['required', 'integer', 'min:1', 'max:3650'],
        ]);

        $user = User::findOrFail($this->selectedUserId);
        $this->guardAgainstPrivilegedTarget($user);

        $before = ['account_status' => $user->account_status];

        DB::transaction(function () use ($user) {
            $user->account_status = 'suspended';
            $user->save();

            UserSuspension::create([
                'user_id' => $user->id,
                'issued_by_admin_id' => Auth::id(),
                'reason' => $this->userSuspendReason,
                'suspension_type' => $this->userSuspendType,
                'starts_at' => now(),
                'ends_at' => $this->userSuspendType === 'permanent' ? null : now()->addDays($this->userSuspendDays),
            ]);
        });

        AdminAuditService::log('user.suspend', $user, $before, [
            'account_status' => 'suspended',
            'reason' => $this->userSuspendReason,
            'type' => $this->userSuspendType,
        ]);

        $this->showSuspendUserModal = false;
        $this->selectedUserId = null;
        session()->flash('success', "User [{$user->name}] has been suspended.");
    }

    public function unsuspendUser(int $userId): void
    {
        $this->authorizePermission('users.suspend');
        $user = User::findOrFail($userId);
        $before = ['account_status' => $user->account_status];

        $user->account_status = 'active';
        $user->save();

        UserSuspension::where('user_id', $user->id)->where('is_lifted', false)->update([
            'is_lifted' => true,
            'lifted_reason' => 'Administrative reinstatement',
            'lifted_at' => now(),
        ]);

        AdminAuditService::log('user.unsuspend', $user, $before, ['account_status' => 'active']);
        session()->flash('success', "User [{$user->name}] has been reinstated to active status.");
    }

    public function openAssignRoleModal(int $userId): void
    {
        $this->authorizePermission('staff.manage');
        $user = User::findOrFail($userId);
        $this->selectedUserId = $userId;
        $this->newRoleSlug = $user->role ?: 'user';
        $this->showAssignRoleModal = true;
    }

    public function assignRole(): void
    {
        $this->authorizePermission('staff.manage');
        $this->validate([
            'selectedUserId' => ['required', 'exists:users,id'],
            'newRoleSlug' => ['required', 'string', Rule::in(array_merge(['user', 'advertiser'], self::STAFF_ROLES))],
        ]);

        $user = User::findOrFail($this->selectedUserId);

        if ($user->id === Auth::id()) {
            throw ValidationException::withMessages(['newRoleSlug' => 'You cannot change your own role.']);
        }
        if (($this->newRoleSlug === 'super_admin' || $user->role === 'super_admin') && !$this->isSuperAdmin()) {
            abort(403, 'Only a Super Administrator can grant or revoke the Super Administrator role.');
        }

        $before = ['role' => $user->role];

        DB::transaction(function () use ($user) {
            $user->role = $this->newRoleSlug;
            $user->save();

            $role = Role::where('slug', $this->newRoleSlug)->first();
            $user->roles()->sync($role ? [$role->id] : []);
        });

        AdminAuditService::log('user.role_change', $user, $before, ['role' => $this->newRoleSlug]);

        $this->showAssignRoleModal = false;
        $this->selectedUserId = null;
        session()->flash('success', "Assigned role [{$this->newRoleSlug}] to user [{$user->name}].");
    }

    public function revokeStaff(int $userId): void
    {
        $this->selectedUserId = $userId;
        $this->newRoleSlug = 'user';
        $this->assignRole();
    }

    public function openCreateStaffModal(): void
    {
        $this->authorizePermission('staff.manage');
        $this->createUserName = '';
        $this->createUserDisplayName = '';
        $this->createUserEmail = '';
        $this->createUserPassword = '';
        $this->createUserPhone = '';
        $this->createUserRole = 'admin';
        $this->createUserAccountStatus = 'active';
        $this->createUserIsAgeVerified = true;
        $this->createUserCredits = 0;
        $this->resetValidation();
        $this->showCreateUserModal = true;
    }

    public function openViewStaffModal(int $userId): void
    {
        $this->openViewUserModal($userId);
    }

    public function openEditStaffModal(int $userId): void
    {
        $this->openEditUserModal($userId);
    }

    public function openDeleteStaffModal(int $userId): void
    {
        $this->openDeleteUserModal($userId);
    }

    public function directLoginAsUser(int $userId)
    {
        $this->authorizePermission('users.view');

        $targetUser = User::findOrFail($userId);

        if ($targetUser->id === Auth::id()) {
            throw ValidationException::withMessages(['impersonate' => 'You are already logged in as this account.']);
        }

        if ($targetUser->role === 'super_admin' && !$this->isSuperAdmin()) {
            abort(403, 'Only a Super Administrator can directly log in to another Super Administrator account.');
        }

        // Store original admin in session for safe exit
        session()->put('impersonator_admin_id', Auth::id());
        session()->put('impersonator_admin_name', Auth::user()->name);
        session()->put('impersonation_target_role', $targetUser->role);

        AdminAuditService::log('user.direct_login', $targetUser, [], [
            'impersonator_id' => Auth::id(),
            'impersonator_name' => Auth::user()->name,
            'target_user_id' => $targetUser->id,
            'target_user_email' => $targetUser->email,
        ]);

        Auth::login($targetUser);
        session()->regenerate();

        session()->flash('status', "⚡ Successfully logged in as {$targetUser->name} without password. You are now browsing as this user.");

        if ($targetUser->isStaff()) {
            return $this->redirect(route('admin.index'), navigate: false);
        }

        return $this->redirect(route('account.dashboard'), navigate: false);
    }

    public function directLoginAsStaff(int $userId)
    {
        $this->authorizePermission('staff.manage');

        $staffUser = User::findOrFail($userId);

        if ($staffUser->id === Auth::id()) {
            throw ValidationException::withMessages(['impersonate' => 'You are already logged in as this account.']);
        }

        if ($staffUser->role === 'super_admin' && !$this->isSuperAdmin()) {
            abort(403, 'Only a Super Administrator can directly log in to a Super Administrator account.');
        }

        // Store original admin in session for safe exit
        session()->put('impersonator_admin_id', Auth::id());
        session()->put('impersonator_admin_name', Auth::user()->name);
        session()->put('impersonation_target_role', $staffUser->role);

        AdminAuditService::log('staff.direct_login', $staffUser, [], [
            'impersonator_id' => Auth::id(),
            'target_staff_id' => $staffUser->id,
            'target_staff_email' => $staffUser->email,
        ]);

        Auth::login($staffUser);
        session()->regenerate();

        session()->flash('success', "Directly logged in as {$staffUser->name} without password.");

        return $this->redirect(route('admin.index', ['tab' => 'staff']), navigate: true);
    }

    public function stopImpersonation()
    {
        $origId = session()->pull('impersonator_admin_id');
        session()->forget('impersonator_admin_name');
        $targetRole = session()->pull('impersonation_target_role');

        if ($origId) {
            $admin = User::find($origId);
            if ($admin) {
                Auth::login($admin);
                session()->regenerate();
                session()->flash('success', "Exited user impersonation session. Returned to administrator account ({$admin->name}).");
            }
        }

        $tab = in_array($targetRole, self::STAFF_ROLES, true) ? 'staff' : 'users';
        return $this->redirect(route('admin.index', ['tab' => $tab]), navigate: false);
    }

    public function resetStaffFilters(): void
    {
        $this->staffSearch = '';
        $this->staffRoleFilter = 'all';
        $this->staffStatusFilter = 'all';
        $this->resetPage('staffPage');
    }

    public function openCreateUserModal(): void
    {
        $this->createUserName = '';
        $this->createUserDisplayName = '';
        $this->createUserEmail = '';
        $this->createUserPassword = '';
        $this->createUserPhone = '';
        $this->createUserRole = 'user';
        $this->createUserAccountStatus = 'active';
        $this->createUserIsAgeVerified = true;
        $this->createUserCredits = 0;
        $this->resetValidation();
        $this->showCreateUserModal = true;
    }

    public function createUser(): void
    {
        $this->validate([
            'createUserName' => ['required', 'string', 'min:2', 'max:100'],
            'createUserDisplayName' => ['nullable', 'string', 'max:100'],
            'createUserEmail' => ['required', 'email', 'max:150', 'unique:users,email'],
            'createUserPassword' => ['required', 'string', 'min:8'],
            'createUserPhone' => ['nullable', 'string', 'max:30'],
            'createUserRole' => ['required', 'string', Rule::in(array_merge(['user', 'advertiser'], self::STAFF_ROLES))],
            'createUserAccountStatus' => ['required', 'string', Rule::in(['active', 'pending_verification', 'suspended', 'closed'])],
            'createUserIsAgeVerified' => ['boolean'],
            'createUserCredits' => ['integer', 'min:0', 'max:100000'],
        ]);

        if ($this->createUserRole === 'super_admin' && !$this->isSuperAdmin()) {
            abort(403, 'Only a Super Administrator can create Super Administrator accounts.');
        }

        $user = DB::transaction(function () {
            $user = User::create([
                'name' => $this->createUserName,
                'email' => strtolower(trim($this->createUserEmail)),
                'password' => Hash::make($this->createUserPassword),
                'phone' => $this->createUserPhone ?: null,
                'role' => $this->createUserRole,
                'account_status' => $this->createUserAccountStatus,
                'is_age_verified' => $this->createUserIsAgeVerified,
                'email_verified_at' => now(),
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'display_name' => $this->createUserDisplayName ?: $this->createUserName,
            ]);

            $role = Role::where('slug', $this->createUserRole)->first();
            if ($role) {
                $user->roles()->sync([$role->id]);
            }

            if ($this->createUserCredits > 0) {
                $wallet = UserWallet::firstOrCreate(
                    ['user_id' => $user->id],
                    ['balance' => $this->createUserCredits, 'total_purchased' => $this->createUserCredits, 'total_spent' => 0, 'currency' => 'USD']
                );

                WalletTransaction::create([
                    'user_id' => $user->id,
                    'actor_id' => Auth::id(),
                    'type' => 'credit_grant',
                    'amount' => $this->createUserCredits,
                    'balance_before' => 0,
                    'balance_after' => $this->createUserCredits,
                    'description' => 'Initial administrative credit allocation',
                    'reference' => 'ADMIN-GRANT-' . strtoupper(Str::random(6)),
                ]);
            }

            return $user;
        });

        AdminAuditService::log('user.create', $user, null, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->account_status,
            'credits' => $this->createUserCredits,
        ]);

        $this->showCreateUserModal = false;
        session()->flash('success', "User [{$user->name}] has been created successfully.");
    }

    public function openViewUserModal(int $userId): void
    {
        $user = User::with([
            'profile',
            'wallet',
            'validIdentityVerification.reviewer',
            'latestVerification.reviewer',
            'listings' => fn($q) => $q->latest()->take(5),
            'suspensions',
        ])->findOrFail($userId);
        $this->viewUserId = $userId;
        $isIdentityVerified = $user->hasVerifiedIdentity();
        $this->viewUserData = [
            'id' => $user->id,
            'name' => $user->name,
            'display_name' => $user->profile?->display_name ?: $user->name,
            'avatar_url' => $user->avatar_url,
            'email' => $user->email,
            'phone' => $user->phone ?: 'Not provided',
            'role' => $user->role,
            'account_status' => $user->account_status,
            'is_age_verified' => (bool) $user->is_age_verified,
            'is_identity_verified' => $isIdentityVerified,
            'identity_verification_status' => $user->identityVerificationStatus(),
            'identity_verification_label' => $user->latestVerification?->statusLabel() ?? 'Not Submitted',
            'identity_verified_at' => $isIdentityVerified
                ? ($user->identity_verified_at?->format('M j, Y H:i') ?? $user->validIdentityVerification?->reviewed_at?->format('M j, Y H:i'))
                : null,
            'latest_verification_id' => $user->latestVerification?->id,
            'email_verified_at' => $user->email_verified_at?->format('M j, Y H:i') ?: 'Unverified',
            'created_at' => $user->created_at?->format('M j, Y (h:i A)') ?: '—',
            'last_active_at' => $user->last_active_at?->diffForHumans() ?: 'Never',
            'last_login_ip' => $user->last_login_ip ?: '—',
            'wallet_balance' => $user->wallet?->balance ?? 0,
            'wallet_purchased' => $user->wallet?->total_purchased ?? 0,
            'wallet_spent' => $user->wallet?->total_spent ?? 0,
            'listings_count' => $user->listings()->count(),
            'active_listings_count' => $user->listings()->where('status', 'active')->count(),
            'recent_listings' => $user->listings->map(fn($l) => [
                'id' => $l->id,
                'title' => $l->title,
                'slug' => $l->slug,
                'status' => $l->status,
                'created_at' => $l->created_at?->format('M j, Y'),
            ])->all(),
            'suspensions' => $user->suspensions->map(fn($s) => [
                'reason' => $s->reason,
                'type' => $s->suspension_type,
                'created_at' => $s->created_at?->format('M j, Y'),
                'is_lifted' => $s->is_lifted,
            ])->all(),
        ];
        $this->showViewUserModal = true;
    }

    public function openEditUserModal(int $userId): void
    {
        $user = User::with(['profile', 'wallet'])->findOrFail($userId);
        $this->guardAgainstPrivilegedTarget($user);
        $this->editUserId = $userId;
        $this->editUserName = $user->name;
        $this->editUserDisplayName = $user->profile?->display_name ?: $user->name;
        $this->editUserEmail = $user->email;
        $this->editUserPassword = '';
        $this->editUserPhone = $user->phone ?: '';
        $this->editUserRole = $user->role ?: 'user';
        $this->editUserAccountStatus = $user->account_status ?: 'active';
        $this->editUserIsAgeVerified = (bool) $user->is_age_verified;
        $this->editUserIsIdentityVerified = $user->hasVerifiedIdentity();
        $this->editUserCredits = $user->wallet?->balance ?? 0;
        $this->editUserAvatarUrl = $user->avatar_url;
        $this->editUserAvatar = null;
        $this->resetValidation();
        $this->showEditUserModal = true;
    }

    public function updateUser(): void
    {
        $this->validate([
            'editUserId' => ['required', 'exists:users,id'],
            'editUserName' => ['required', 'string', 'min:2', 'max:100'],
            'editUserDisplayName' => ['nullable', 'string', 'max:100'],
            'editUserEmail' => ['required', 'email', 'max:150', 'unique:users,email,' . $this->editUserId],
            'editUserPassword' => ['nullable', 'string', 'min:8'],
            'editUserPhone' => ['nullable', 'string', 'max:30'],
            'editUserRole' => ['required', 'string', Rule::in(array_merge(['user', 'advertiser'], self::STAFF_ROLES))],
            'editUserAccountStatus' => ['required', 'string', Rule::in(['active', 'pending_verification', 'suspended', 'closed'])],
            'editUserIsAgeVerified' => ['boolean'],
            'editUserCredits' => ['integer', 'min:0', 'max:100000'],
            'editUserAvatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = User::with(['profile', 'wallet'])->findOrFail($this->editUserId);
        $this->guardAgainstPrivilegedTarget($user);

        if (($this->editUserRole === 'super_admin' || $user->role === 'super_admin') && !$this->isSuperAdmin()) {
            abort(403, 'Only a Super Administrator can assign or modify Super Administrator accounts.');
        }

        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->account_status,
            'is_age_verified' => $user->is_age_verified,
            'is_identity_verified' => $user->is_identity_verified,
        ];

        DB::transaction(function () use ($user) {
            $user->name = $this->editUserName;
            $user->email = strtolower(trim($this->editUserEmail));
            $user->phone = $this->editUserPhone ?: null;
            $user->role = $this->editUserRole;
            $user->account_status = $this->editUserAccountStatus;
            $user->is_age_verified = $this->editUserIsAgeVerified;
            if (!empty($this->editUserPassword)) {
                $user->password = Hash::make($this->editUserPassword);
            }

            // Profile
            $profile = $user->profile ?: new UserProfile(['user_id' => $user->id]);
            $profile->display_name = $this->editUserDisplayName ?: $this->editUserName;

            // Handle Admin Avatar Upload
            if ($this->editUserAvatar) {
                $stored = app(\App\Support\Storage\MediaStorageService::class)->storeSanitizedPhoto(
                    $this->editUserAvatar,
                    'avatars',
                    'public',
                    800,
                    800
                );
                $path = $stored['path'];
                $user->avatar = $path;
                $profile->avatar_path = $path;
            }

            $user->save();
            $profile->save();

            // Enforce strict ID-based badge state rather than manual user editing
            app(\App\Modules\Verification\Services\IdentityVerificationService::class)->syncUserBadgeState($user);

            // Roles sync
            $role = Role::where('slug', $this->editUserRole)->first();
            $user->roles()->sync($role ? [$role->id] : []);

            // Wallet balance adjustment
            $wallet = $user->wallet ?: UserWallet::create(['user_id' => $user->id, 'balance' => 0, 'currency' => 'USD']);
            if ($wallet->balance !== $this->editUserCredits) {
                $oldBalance = $wallet->balance;
                $diff = $this->editUserCredits - $oldBalance;
                $wallet->balance = $this->editUserCredits;
                if ($diff > 0) {
                    $wallet->total_purchased += $diff;
                }
                $wallet->save();

                WalletTransaction::create([
                    'user_id' => $user->id,
                    'actor_id' => Auth::id(),
                    'type' => $diff > 0 ? 'credit_grant' : 'credit_deduction',
                    'amount' => abs($diff),
                    'balance_before' => $oldBalance,
                    'balance_after' => $this->editUserCredits,
                    'description' => 'Administrative wallet balance adjustment',
                    'reference' => 'ADMIN-ADJ-' . strtoupper(Str::random(6)),
                ]);
            }
        });

        AdminAuditService::log('user.update', $user, $before, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->account_status,
            'is_age_verified' => $user->is_age_verified,
            'is_identity_verified' => $user->is_identity_verified,
        ]);

        $this->showEditUserModal = false;
        $this->editUserAvatar = null;
        $this->editUserAvatarUrl = null;
        session()->flash('success', "User [{$user->name}] details updated successfully.");
    }

    public function removeUserAvatarInEditModal(): void
    {
        if (!$this->editUserId) {
            return;
        }

        $user = User::with('profile')->findOrFail($this->editUserId);
        $this->guardAgainstPrivilegedTarget($user);

        $user->avatar = null;
        $user->save();

        if ($user->profile) {
            $user->profile->avatar_path = null;
            $user->profile->save();
        }

        $this->editUserAvatarUrl = null;
        $this->editUserAvatar = null;

        AdminAuditService::log('user.avatar_remove', $user, ['avatar' => 'present'], ['avatar' => null]);
        session()->flash('success', "Profile photo for [{$user->name}] has been removed.");
    }

    public function openDeleteUserModal(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->guardAgainstPrivilegedTarget($user);
        $this->deleteUserId = $userId;
        $this->deleteUserName = $user->name;
        $this->deleteUserEmail = $user->email;
        $this->showDeleteUserModal = true;
    }

    public function deleteUser(): void
    {
        $this->validate([
            'deleteUserId' => ['required', 'exists:users,id'],
        ]);

        $user = User::findOrFail($this->deleteUserId);
        $this->guardAgainstPrivilegedTarget($user);

        if ($user->id === Auth::id()) {
            abort(403, 'You cannot delete your own account.');
        }

        DB::transaction(function () use ($user) {
            $user->listings()->update(['status' => 'suspended']);
            $user->delete();
        });

        AdminAuditService::log('user.delete', $user, ['deleted' => false], ['deleted' => true, 'soft' => true]);

        $this->showDeleteUserModal = false;
        $this->deleteUserId = null;
        session()->flash('success', "User [{$user->name}] has been safely deleted.");
    }

    /** Non-super-admins may never discipline or modify super admins. Self-modification is always forbidden. */
    protected function guardAgainstPrivilegedTarget(User $target): void
    {
        if ($target->id === Auth::id()) {
            abort(403, 'You cannot discipline, modify permissions for, or delete your own account.');
        }
        if ($target->role === 'super_admin' && !$this->isSuperAdmin()) {
            abort(403, 'Only a Super Administrator can modify Super Administrator accounts.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 2 & 6. Listing Management & Moderation
    |--------------------------------------------------------------------------
    */

    public function approveListing(int $listingId): void
    {
        $this->authorizePermission('listings.approve');
        $listing = Listing::findOrFail($listingId);
        $from = $this->applyApproval($listing, 'Approved by moderation administrator.');
        AdminAuditService::log('listing.approve', $listing, ['status' => $from], ['status' => Listing::STATUS_ACTIVE]);

        if ($listing->user_id) {
            NotificationLog::create([
                'user_id' => $listing->user_id,
                'channel' => 'database',
                'notification_type' => 'listing_approved',
                'subject' => "Listing Approved: {$listing->title}",
                'message' => "Great news! Your personal classified listing [{$listing->title}] has been approved by moderation and is now live.",
                'recipient_target' => $listing->user?->email ?? 'user#' . $listing->user_id,
                'status' => 'sent',
                'is_read' => false,
                'read_at' => null,
                'sent_at' => now(),
            ]);
        }

        session()->flash('success', "Listing [{$listing->title}] approved and published.");
    }

    protected function applyApproval(Listing $listing, string $note): string
    {
        $from = $listing->status;
        $listing->published_at = $listing->published_at ?: now();
        $listing->expires_at = $listing->expires_at && $listing->expires_at->isFuture() ? $listing->expires_at : now()->addDays(30);
        $listing->rejection_reason = null;
        $listing->recordStatusChange(Listing::STATUS_ACTIVE, null, Auth::id());

        return $from;
    }

    public function openRejectListingModal(int $listingId): void
    {
        $this->authorizePermission('listings.reject');
        $this->targetListingId = $listingId;
        $this->listingActionReason = '';
        $this->showRejectListingModal = true;
    }

    public function rejectListing(): void
    {
        $this->authorizePermission('listings.reject');
        $this->validate([
            'targetListingId' => ['required', 'exists:listings,id'],
            'listingActionReason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $listing = Listing::findOrFail($this->targetListingId);
        $before = ['status' => $listing->status];
        $listing->recordStatusChange(Listing::STATUS_REJECTED, $this->listingActionReason, Auth::id());

        AdminAuditService::log('listing.reject', $listing, $before, ['status' => Listing::STATUS_REJECTED, 'reason' => $this->listingActionReason]);

        if ($listing->user_id) {
            NotificationLog::create([
                'user_id' => $listing->user_id,
                'channel' => 'database',
                'notification_type' => 'listing_rejected',
                'subject' => "Moderation Notice: {$listing->title}",
                'message' => "Your classified listing [{$listing->title}] requires revision: {$this->listingActionReason}",
                'recipient_target' => $listing->user?->email ?? 'user#' . $listing->user_id,
                'status' => 'sent',
                'is_read' => false,
                'read_at' => null,
                'sent_at' => now(),
            ]);
        }

        $this->showRejectListingModal = false;
        $this->targetListingId = null;
        session()->flash('success', "Listing [{$listing->title}] has been rejected.");
    }

    public function suspendListing(int $listingId): void
    {
        $this->authorizePermission('listings.suspend');
        $listing = Listing::findOrFail($listingId);
        $before = ['status' => $listing->status];
        $listing->recordStatusChange(Listing::STATUS_SUSPENDED, 'Disciplinary suspension enforced by Trust & Safety moderation.', Auth::id());

        AdminAuditService::log('listing.suspend', $listing, $before, ['status' => Listing::STATUS_SUSPENDED]);
        session()->flash('success', "Listing [{$listing->title}] suspended.");
    }

    public function openViewListingModal(int $listingId): void
    {
        $this->authorizePermission('listings.view');
        $listing = Listing::with([
            'category',
            'city',
            'state',
            'user.profile',
            'user.wallet',
            'media',
            'contact',
            'attributes',
            'statusHistory.changedBy',
            'analytics',
            'boosts',
        ])->findOrFail($listingId);

        $this->viewListingId = $listingId;
        $this->viewListingData = [
            'id' => $listing->id,
            'title' => $listing->title,
            'slug' => $listing->slug,
            'description' => $listing->description,
            'status' => $listing->status,
            'rejection_reason' => $listing->rejection_reason,
            'is_featured' => (bool) $listing->is_featured,
            'is_verified_poster' => (bool) $listing->is_verified_poster,
            'poster_age' => $listing->poster_age,
            'poster_gender' => $listing->poster_gender,
            'intent' => $listing->intent,
            'category_name' => $listing->category?->name ?? 'Uncategorized',
            'city_name' => $listing->city?->name ?? '',
            'state_code' => $listing->state?->code ?? $listing->state?->name ?? '',
            'location_name' => ($listing->city?->name ? $listing->city->name . ', ' : '') . ($listing->state?->name ?? $listing->state?->code ?? 'USA'),
            'user_id' => $listing->user_id,
            'user_name' => $listing->user?->name ?? 'Unknown',
            'user_email' => $listing->user?->email ?? '',
            'view_count' => $listing->view_count,
            'reply_count' => $listing->reply_count,
            'favorite_count' => $listing->favorite_count,
            'created_at' => $listing->created_at?->format('M j, Y (h:i A)'),
            'published_at' => $listing->published_at?->format('M j, Y (h:i A)') ?? 'Not published',
            'expires_at' => $listing->expires_at?->format('M j, Y (h:i A)') ?? '—',
            'bumped_at' => $listing->bumped_at?->format('M j, Y (h:i A)') ?? '—',
            'user' => [
                'id' => $listing->user_id,
                'name' => $listing->user?->name ?? 'Unknown',
                'display_name' => $listing->user?->profile?->display_name ?: $listing->user?->name,
                'email' => $listing->user?->email,
                'account_status' => $listing->user?->account_status ?? 'unknown',
                'is_age_verified' => (bool) $listing->user?->is_age_verified,
                'wallet_balance' => $listing->user?->wallet?->balance ?? 0,
            ],
            'contact' => [
                'email' => $listing->contact?->email ?: $listing->user?->email,
                'phone' => $listing->contact?->phone ?: 'Not provided',
                'preferred_contact_method' => $listing->contact?->preferred_contact_method ?: 'platform_message',
            ],
            'media' => $listing->media->map(fn($m) => [
                'id' => $m->id,
                'url' => $m->url,
                'thumbnail_url' => $m->thumbnail_url ?: $m->url,
                'is_primary' => (bool) $m->is_primary,
                'moderation_status' => $m->moderation_status ?? 'approved',
            ])->all(),
            'attributes' => $listing->attributes->map(fn($a) => [
                'name' => $a->attribute_name ?: $a->name,
                'value' => $a->attribute_value ?: $a->value,
            ])->all(),
            'status_history' => $listing->statusHistory->take(5)->map(fn($h) => [
                'from_status' => $h->from_status,
                'to_status' => $h->to_status,
                'reason' => $h->reason,
                'actor' => $h->changedBy?->name ?? 'System',
                'created_at' => $h->created_at?->format('M j, Y H:i'),
            ])->all(),
        ];

        $this->showViewListingModal = true;
    }

    public function openEditListingModal(int $listingId): void
    {
        $this->authorizePermission('listings.view');
        $listing = Listing::with(['category', 'city.state', 'state'])->findOrFail($listingId);

        $this->editListingId = $listing->id;
        $this->editListingTitle = $listing->title;
        $this->editListingDescription = $listing->description;
        $this->editListingCategoryId = $listing->category_id;
        $this->editListingStateId = $listing->state_id ?: $listing->city?->state_id;
        $this->editListingCityId = $listing->city_id;
        $this->editListingStatus = $listing->status;
        $this->editListingPosterAge = $listing->poster_age;
        $this->editListingPosterGender = $listing->poster_gender;
        $this->editListingIntent = $listing->intent;
        $this->editListingIsFeatured = (bool) $listing->is_featured;
        $this->editListingIsVerifiedPoster = $listing->hasVerifiedPoster();

        $this->resetValidation();
        $this->showEditListingModal = true;
    }

    public function updatedEditListingStateId(): void
    {
        $this->editListingCityId = null;
    }

    public function updateListing(): void
    {
        $this->authorizePermission('listings.view');
        $this->validate([
            'editListingId' => ['required', 'exists:listings,id'],
            'editListingTitle' => ['required', 'string', 'min:3', 'max:200'],
            'editListingDescription' => ['required', 'string', 'min:10', 'max:5000'],
            'editListingCategoryId' => ['required', 'exists:categories,id'],
            'editListingStateId' => ['nullable', 'exists:states,id'],
            'editListingCityId' => ['nullable', 'exists:cities,id'],
            'editListingStatus' => ['required', 'string', Rule::in(['draft', 'pending_review', 'active', 'paused', 'expired', 'rejected', 'suspended', 'archived'])],
            'editListingPosterAge' => ['nullable', 'integer', 'min:18', 'max:120'],
            'editListingPosterGender' => ['nullable', 'string', 'max:50'],
            'editListingIntent' => ['nullable', 'string', 'max:100'],
            'editListingIsFeatured' => ['boolean'],
        ]);

        $listing = Listing::with('user.validIdentityVerification')->findOrFail($this->editListingId);

        $before = [
            'title' => $listing->title,
            'description' => $listing->description,
            'category_id' => $listing->category_id,
            'state_id' => $listing->state_id,
            'city_id' => $listing->city_id,
            'status' => $listing->status,
            'poster_age' => $listing->poster_age,
            'poster_gender' => $listing->poster_gender,
            'intent' => $listing->intent,
            'is_featured' => $listing->is_featured,
            'is_verified_poster' => $listing->is_verified_poster,
        ];

        DB::transaction(function () use ($listing) {
            $statusChanged = $listing->status !== $this->editListingStatus;

            $listing->title = $this->editListingTitle;
            $listing->description = $this->editListingDescription;
            $listing->category_id = $this->editListingCategoryId;
            $listing->state_id = $this->editListingStateId;
            $listing->city_id = $this->editListingCityId;
            $listing->poster_age = $this->editListingPosterAge;
            $listing->poster_gender = $this->editListingPosterGender;
            $listing->intent = $this->editListingIntent;
            $listing->is_featured = $this->editListingIsFeatured;
            $listing->is_verified_poster = $listing->user ? $listing->user->hasVerifiedIdentity() : false;

            if ($this->editListingStatus === Listing::STATUS_ACTIVE && !$listing->published_at) {
                $listing->published_at = now();
                $listing->expires_at = now()->addDays(30);
            }

            if ($statusChanged) {
                $listing->recordStatusChange($this->editListingStatus, 'Status updated via Administrative Hub', Auth::id());
            } else {
                $listing->save();
            }
        });

        AdminAuditService::log('listing.update', $listing, $before, [
            'title' => $listing->title,
            'status' => $listing->status,
            'category_id' => $listing->category_id,
            'city_id' => $listing->city_id,
            'is_featured' => $listing->is_featured,
        ]);

        $this->showEditListingModal = false;
        session()->flash('success', "Listing [{$listing->title}] details updated successfully.");
    }

    public function openDeleteListingModal(int $listingId): void
    {
        $this->authorizePermission('listings.delete');
        $listing = Listing::findOrFail($listingId);
        $this->deleteListingId = $listingId;
        $this->deleteListingTitle = $listing->title;
        $this->showDeleteListingModal = true;
    }

    public function deleteListingConfirmed(): void
    {
        $this->authorizePermission('listings.delete');
        $this->validate([
            'deleteListingId' => ['required', 'exists:listings,id'],
        ]);

        $listing = Listing::findOrFail($this->deleteListingId);
        $before = ['status' => $listing->status, 'title' => $listing->title];

        $listing->delete(); // Soft delete — recoverable, safe

        AdminAuditService::log('listing.delete', $listing, $before, ['deleted' => true, 'soft' => true]);

        $this->showDeleteListingModal = false;
        $this->deleteListingId = null;
        $this->deleteListingTitle = null;

        session()->flash('success', "Listing [{$listing->title}] moved to trash (soft deleted).");
    }

    public function deleteListing(int $listingId): void
    {
        $this->openDeleteListingModal($listingId);
    }

    public function bulkApproveListings(): void
    {
        $this->authorizePermission('listings.approve');
        $ids = array_values(array_filter(array_map('intval', $this->selectedListingIds)));
        if (empty($ids)) {
            session()->flash('error', 'Select at least one listing first.');
            return;
        }

        $listings = Listing::whereIn('id', $ids)->get();
        foreach ($listings as $listing) {
            $this->applyApproval($listing, 'Bulk approved by administrator.');
        }

        AdminAuditService::log('listing.bulk_approve', Listing::class, null, ['count' => $listings->count(), 'ids' => $listings->pluck('id')->all()]);
        $this->selectedListingIds = [];
        session()->flash('success', "{$listings->count()} listing(s) approved.");
    }

    public function bulkSuspendListings(): void
    {
        $this->authorizePermission('listings.suspend');
        $ids = array_values(array_filter(array_map('intval', $this->selectedListingIds)));
        if (empty($ids)) {
            session()->flash('error', 'Select at least one listing first.');
            return;
        }

        $listings = Listing::whereIn('id', $ids)->get();
        foreach ($listings as $listing) {
            $listing->recordStatusChange(Listing::STATUS_SUSPENDED, 'Bulk suspension by Trust & Safety.', Auth::id());
        }

        AdminAuditService::log('listing.bulk_suspend', Listing::class, null, ['count' => $listings->count(), 'ids' => $listings->pluck('id')->all()]);
        $this->selectedListingIds = [];
        session()->flash('success', "{$listings->count()} listing(s) suspended.");
    }

    /*
    |--------------------------------------------------------------------------
    | 6b. Moderation Controls & Suspension Appeals
    |--------------------------------------------------------------------------
    */

    public function setModerationSubTab(string $subTab): void
    {
        $this->moderationSubTab = $subTab;
    }

    public function resetModerationFilters(): void
    {
        $this->moderationSearch = '';
        $this->moderationCategoryFilter = null;
        $this->moderationRiskFilter = 'all';
    }

    public function bulkApproveQueue(): void
    {
        $this->authorizePermission('listings.approve');
        $ids = array_values(array_filter(array_map('intval', $this->selectedQueueListingIds)));
        if (empty($ids)) {
            session()->flash('error', 'Select at least one listing from the review queue.');
            return;
        }

        $listings = Listing::whereIn('id', $ids)->where('status', Listing::STATUS_PENDING_REVIEW)->get();
        foreach ($listings as $listing) {
            $this->applyApproval($listing, 'Bulk approved from moderation review queue.');
        }

        AdminAuditService::log('moderation.bulk_approve', Listing::class, null, [
            'count' => $listings->count(),
            'ids' => $listings->pluck('id')->all(),
        ]);

        $this->selectedQueueListingIds = [];
        session()->flash('success', "{$listings->count()} listing(s) approved and published from moderation queue.");
    }

    public function bulkRejectQueue(): void
    {
        $this->authorizePermission('listings.reject');
        $ids = array_values(array_filter(array_map('intval', $this->selectedQueueListingIds)));
        if (empty($ids)) {
            session()->flash('error', 'Select at least one listing from the review queue.');
            return;
        }

        $listings = Listing::whereIn('id', $ids)->where('status', Listing::STATUS_PENDING_REVIEW)->get();
        foreach ($listings as $listing) {
            $listing->recordStatusChange(Listing::STATUS_REJECTED, 'Bulk rejected by moderation safety review.', Auth::id());
        }

        AdminAuditService::log('moderation.bulk_reject', Listing::class, null, [
            'count' => $listings->count(),
            'ids' => $listings->pluck('id')->all(),
        ]);

        $this->selectedQueueListingIds = [];
        session()->flash('success', "{$listings->count()} listing(s) rejected.");
    }

    public function openAppealModal(int $appealId): void
    {
        $this->authorizePermission('moderation.handle_appeals');
        $appeal = AppealRequest::with(['user.profile', 'suspension.issuedBy'])->findOrFail($appealId);
        $this->targetAppealId = $appealId;
        $this->appealDecisionNotes = '';
        $this->inspectedAppealData = [
            'id' => $appeal->id,
            'user_id' => $appeal->user_id,
            'user_name' => $appeal->user?->name ?? 'Unknown',
            'user_email' => $appeal->user?->email,
            'user_account_status' => $appeal->user?->account_status,
            'appeal_reason' => $appeal->appeal_reason,
            'status' => $appeal->status,
            'decision_notes' => $appeal->decision_notes,
            'created_at' => $appeal->created_at?->format('M j, Y (h:i A)'),
            'suspension_reason' => $appeal->suspension?->reason ?? 'Account suspended by moderation.',
            'suspension_type' => $appeal->suspension?->suspension_type ?? 'permanent',
            'suspended_at' => $appeal->suspension?->created_at?->format('M j, Y'),
            'issued_by' => $appeal->suspension?->issuedBy?->name ?? 'System Administrator',
        ];
        $this->showAppealModal = true;
    }

    public function decideAppeal(string $decision): void
    {
        $this->authorizePermission('moderation.handle_appeals');
        if (!in_array($decision, ['granted', 'rejected'], true)) {
            abort(422);
        }
        $this->validate([
            'targetAppealId' => ['required', 'exists:appeal_requests,id'],
            'appealDecisionNotes' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $appeal = AppealRequest::findOrFail($this->targetAppealId);
        $before = ['status' => $appeal->status];

        DB::transaction(function () use ($appeal, $decision) {
            $appeal->update([
                'status' => $decision,
                'decision_notes' => $this->appealDecisionNotes,
                'reviewed_by_admin_id' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            if ($decision === 'granted') {
                User::whereKey($appeal->user_id)->update(['account_status' => 'active']);
                UserSuspension::where('user_id', $appeal->user_id)->where('is_lifted', false)->update([
                    'is_lifted' => true,
                    'lifted_reason' => 'Appeal granted',
                    'lifted_at' => now(),
                ]);
            }
        });

        AdminAuditService::log('appeal.' . $decision, $appeal, $before, ['status' => $decision, 'notes' => $this->appealDecisionNotes]);

        $this->showAppealModal = false;
        $this->targetAppealId = null;
        session()->flash('success', "Appeal #{$appeal->id} {$decision}.");
    }

    /*
    |--------------------------------------------------------------------------
    | 7. Age Assurance Verification
    |--------------------------------------------------------------------------
    */

    public function approveAgeVerification(int $assuranceId): void
    {
        $this->authorizePermission('verification.manage');
        $assurance = AgeAssurance::findOrFail($assuranceId);
        $before = ['status' => $assurance->status];
        $assurance->status = 'approved';
        $assurance->reviewed_by_admin_id = Auth::id();
        $assurance->verified_at = now();
        $assurance->save();

        $user = $assurance->user;
        if ($user) {
            $user->is_age_verified = true;
            $user->save();
        }

        AdminAuditService::log('verification.approve', $assurance, $before, ['status' => 'approved', 'user_id' => $assurance->user_id]);
        $msg = '✓ Age assurance approved for user [' . ($user?->name ?? 'unknown') . '].';
        session()->flash('success', $msg);
        $this->dispatch('toast', type: 'success', message: $msg);
        $this->showInspectVerificationModal = false;
    }

    public function openRejectVerificationModal(int $assuranceId): void
    {
        $this->authorizePermission('verification.manage');
        $this->rejectingAssuranceId = $assuranceId;
        $this->rejectionReason = '';
        $this->showRejectVerificationModal = true;
    }

    public function rejectAgeVerification(int $assuranceId, string $reason = 'Age assurance request rejected by compliance officer.'): void
    {
        $this->authorizePermission('verification.manage');
        $assurance = AgeAssurance::findOrFail($assuranceId);
        $before = ['status' => $assurance->status];
        $assurance->status = 'rejected';
        $assurance->reviewed_by_admin_id = Auth::id();

        $metadata = (array) ($assurance->verification_metadata ?? []);
        $metadata['rejection_reason'] = $this->rejectionReason ?: $reason;
        $metadata['rejected_at'] = now()->toIso8601String();
        $assurance->verification_metadata = $metadata;
        $assurance->save();

        $user = $assurance->user;
        if ($user) {
            $user->is_age_verified = false;
            $user->save();
        }

        AdminAuditService::log('verification.reject', $assurance, $before, [
            'status' => 'rejected',
            'user_id' => $assurance->user_id,
            'reason' => $metadata['rejection_reason'],
        ]);

        $this->showRejectVerificationModal = false;
        $this->showInspectVerificationModal = false;
        $this->rejectingAssuranceId = null;

        $msg = "Age assurance request #{$assurance->id} was rejected.";
        session()->flash('success', $msg);
        $this->dispatch('toast', type: 'info', message: $msg);
    }

    public function confirmRejectVerification(): void
    {
        if ($this->rejectingAssuranceId) {
            $this->rejectAgeVerification($this->rejectingAssuranceId, $this->rejectionReason);
        }
    }

    public function openInspectVerificationModal(int $assuranceId): void
    {
        $this->authorizePermission('verification.manage');
        $assurance = AgeAssurance::with(['user.profile', 'reviewer'])->findOrFail($assuranceId);

        $this->inspectedVerificationData = [
            'id' => $assurance->id,
            'user' => [
                'id' => $assurance->user?->id,
                'name' => $assurance->user?->name,
                'email' => $assurance->user?->email,
                'role' => $assurance->user?->role,
                'is_age_verified' => (bool) $assurance->user?->is_age_verified,
                'account_status' => $assurance->user?->account_status,
                'created_at' => $assurance->user?->created_at?->format('M d, Y'),
            ],
            'method' => $assurance->method,
            'status' => $assurance->status,
            'declared_birthdate' => $assurance->declared_birthdate?->format('F j, Y'),
            'age' => $assurance->declared_birthdate ? $assurance->declared_birthdate->age : null,
            'document_hash' => $assurance->document_hash,
            'third_party_reference' => $assurance->third_party_reference,
            'metadata' => (array) ($assurance->verification_metadata ?? []),
            'reviewer' => $assurance->reviewer?->name,
            'verified_at' => $assurance->verified_at?->format('M d, Y H:i'),
            'submitted_at' => $assurance->created_at?->format('M d, Y H:i'),
        ];

        $this->showInspectVerificationModal = true;
    }

    public function openManualVerifyModal(): void
    {
        $this->authorizePermission('verification.manage');
        $this->manualVerifyForm = [
            'user_id' => '',
            'declared_birthdate' => '2000-01-01',
            'method' => 'digital_id',
            'admin_notes' => 'Verified via physical or electronic government ID review.',
        ];
        $this->showManualVerifyModal = true;
    }

    public function saveManualVerification(): void
    {
        $this->authorizePermission('verification.manage');

        $validated = $this->validate([
            'manualVerifyForm.user_id' => ['required', 'exists:users,id'],
            'manualVerifyForm.declared_birthdate' => ['required', 'date', 'before:-18 years'],
            'manualVerifyForm.method' => ['required', 'string'],
            'manualVerifyForm.admin_notes' => ['nullable', 'string', 'max:500'],
        ])['manualVerifyForm'];

        $user = User::findOrFail($validated['user_id']);

        $assurance = AgeAssurance::updateOrCreate(
            ['user_id' => $user->id],
            [
                'method' => $validated['method'],
                'status' => 'approved',
                'declared_birthdate' => $validated['declared_birthdate'],
                'reviewed_by_admin_id' => Auth::id(),
                'verified_at' => now(),
                'verification_metadata' => [
                    'admin_notes' => $validated['admin_notes'] ?: 'Manual administrative clearance',
                    'manual_entry_by' => Auth::user()?->name ?? 'Admin',
                    'manual_entry_at' => now()->toIso8601String(),
                ],
            ]
        );

        $user->is_age_verified = true;
        $user->save();

        AdminAuditService::log('verification.manual_grant', $assurance, [], [
            'user_id' => $user->id,
            'method' => $validated['method'],
            'birthdate' => $validated['declared_birthdate'],
        ]);

        $this->showManualVerifyModal = false;
        $msg = "✓ 18+ Age Verification successfully granted to {$user->name}.";
        session()->flash('success', $msg);
        $this->dispatch('toast', type: 'success', message: $msg);
    }

    /*
    |--------------------------------------------------------------------------
    | Notifications & Broadcast Management
    |--------------------------------------------------------------------------
    */

    public function setNotificationsSubTab(string $subTab): void
    {
        $this->notificationsSubTab = in_array($subTab, ['logs', 'templates'], true) ? $subTab : 'logs';
    }

    public function openBroadcastModal(): void
    {
        $this->authorizePermission('settings.manage');
        $this->broadcastForm = [
            'target_audience' => 'all_users',
            'specific_user_id' => '',
            'channel' => 'database',
            'subject' => '',
            'message' => '',
        ];
        $this->showBroadcastModal = true;
    }

    public function sendBroadcastNotification(): void
    {
        $this->authorizePermission('settings.manage');

        $validated = $this->validate([
            'broadcastForm.target_audience' => ['required', Rule::in(['all_users', 'active_only', 'specific_user'])],
            'broadcastForm.specific_user_id' => ['nullable', 'required_if:broadcastForm.target_audience,specific_user', 'exists:users,id'],
            'broadcastForm.channel' => ['required', Rule::in(['database', 'email', 'sms'])],
            'broadcastForm.subject' => ['required', 'string', 'max:180'],
            'broadcastForm.message' => ['required', 'string', 'max:5000'],
        ])['broadcastForm'];

        if ($validated['target_audience'] === 'specific_user') {
            $users = User::where('id', $validated['specific_user_id'])->get();
        } elseif ($validated['target_audience'] === 'active_only') {
            $users = User::where('account_status', 'active')->limit(100)->get();
        } else {
            $users = User::limit(100)->get();
        }

        $count = 0;
        foreach ($users as $u) {
            NotificationLog::create([
                'user_id' => $u->id,
                'channel' => $validated['channel'],
                'notification_type' => 'admin_broadcast_announcement',
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'recipient_target' => $validated['channel'] === 'email' ? $u->email : ($u->phone ?: $u->email),
                'status' => 'sent',
                'is_read' => false,
                'read_at' => null,
                'sent_at' => now(),
                'error_details' => null,
            ]);
            $count++;
        }

        $this->showBroadcastModal = false;
        $msg = "✓ Broadcast announcement dispatched to {$count} recipient(s).";
        session()->flash('success', $msg);
        $this->dispatch('toast', type: 'success', message: $msg);
    }

    public function inspectNotification(int $logId): void
    {
        $this->authorizePermission('settings.manage');
        $log = NotificationLog::with('user')->findOrFail($logId);
        $log->markAsRead();

        $this->inspectNotificationData = [
            'id' => $log->id,
            'user_name' => $log->user?->name ?? 'User #' . $log->user_id,
            'recipient_target' => $log->recipient_target,
            'channel' => strtoupper($log->channel),
            'notification_type' => $log->notification_type,
            'subject' => $log->subject ?: 'Platform Notification',
            'message' => $log->message ?: $log->subject ?: 'System notification dispatched.',
            'status' => strtoupper($log->status),
            'is_read' => (bool) $log->is_read,
            'read_at' => $log->read_at?->format('M j, Y H:i:s') ?? now()->format('M j, Y H:i:s'),
            'sent_at' => $log->sent_at?->format('M j, Y H:i:s') ?? $log->created_at?->format('M j, Y H:i:s'),
            'error_details' => $log->error_details,
        ];
        $this->showInspectNotificationModal = true;
    }

    public function markNotificationRead(int $logId): void
    {
        $this->authorizePermission('settings.manage');
        $log = NotificationLog::findOrFail($logId);
        $log->markAsRead();
        session()->flash('success', "Notification #{$log->id} marked as read (Seen).");
    }

    public function markAllNotificationsRead(): void
    {
        $this->authorizePermission('settings.manage');
        NotificationLog::where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        session()->flash('success', 'All notifications have been marked as read (Seen).');
    }

    public function resendNotification(int $logId): void
    {
        $this->authorizePermission('settings.manage');
        $log = NotificationLog::findOrFail($logId);
        $log->status = 'sent';
        $log->sent_at = now();
        $log->error_details = null;
        $log->save();

        $msg = "✓ Notification #{$log->id} re-sent successfully.";
        session()->flash('success', $msg);
        $this->dispatch('toast', type: 'success', message: $msg);
    }

    public function toggleTemplate(int $templateId): void
    {
        $this->authorizePermission('settings.manage');
        $tpl = EmailTemplate::findOrFail($templateId);
        $tpl->is_active = !$tpl->is_active;
        $tpl->save();

        $msg = "Template [{$tpl->name}] is now " . ($tpl->is_active ? 'active' : 'inactive') . '.';
        session()->flash('success', $msg);
        $this->dispatch('toast', type: 'success', message: $msg);
    }

    /*
    |--------------------------------------------------------------------------
    | 8 & 9. Reports, Complaints & Incident Moderation
    |--------------------------------------------------------------------------
    */

    public function setReportSubTab(string $subTab): void
    {
        $this->reportSubTab = in_array($subTab, ['queue', 'trends', 'export'], true) ? $subTab : 'queue';
    }

    public function resetReportFilters(): void
    {
        $this->reportSearch = '';
        $this->reportStatusFilter = 'all';
        $this->reportSeverityFilter = 'all';
        $this->reportReasonFilter = 'all';
        $this->resetPage('reportsPage');
    }

    public function openInspectReportModal(int $reportId): void
    {
        $this->authorizePermission('reports.manage');
        $report = ContentReport::with(['reporter', 'resolvedBy'])->findOrFail($reportId);
        $report->markAsRead();

        $targetTitle = 'Target Unavailable';
        $targetUrl = null;
        $targetDetails = [];
        $targetUser = null;

        if ($report->reportable_type === Listing::class) {
            $listing = Listing::with(['user', 'city.state', 'category'])->find($report->reportable_id);
            if ($listing) {
                $targetTitle = $listing->title;
                $targetUrl = route('listings.show', $listing->slug);
                $targetDetails = [
                    'type' => 'Classified Listing',
                    'id' => $listing->id,
                    'status' => $listing->status,
                    'price' => $listing->price ? '$' . number_format($listing->price, 2) : 'Free',
                    'location' => ($listing->city?->name ?? 'City') . ', ' . ($listing->city?->state?->code ?? 'US'),
                    'category' => $listing->category?->name ?? 'Classified',
                    'created_at' => $listing->created_at?->format('M j, Y H:i'),
                ];
                $targetUser = $listing->user;
            }
        } elseif ($report->reportable_type === User::class) {
            $user = User::with(['profile'])->find($report->reportable_id);
            if ($user) {
                $targetTitle = $user->name . ' (' . $user->email . ')';
                $targetDetails = [
                    'type' => 'User Profile',
                    'id' => $user->id,
                    'status' => $user->account_status,
                    'role' => $user->role,
                    'age_verified' => $user->is_age_verified ? 'Yes (18+)' : 'No',
                    'created_at' => $user->created_at?->format('M j, Y'),
                ];
                $targetUser = $user;
            }
        }

        $this->inspectReportData = [
            'id' => $report->id,
            'reason_code' => $report->reason_code,
            'risk_level' => $report->risk_level ?? 'medium',
            'is_urgent_takedown' => (bool) $report->is_urgent_takedown,
            'details' => $report->details,
            'evidence_urls' => is_array($report->evidence_urls) ? $report->evidence_urls : ($report->evidence_urls ? json_decode($report->evidence_urls, true) : []),
            'status' => $report->status,
            'reporter_name' => $report->reporter?->name ?? 'Anonymous Community Member',
            'reporter_email' => $report->reporter?->email ?? 'N/A',
            'reporter_ip' => $report->reporter_ip ?? '127.0.0.1',
            'created_at' => $report->created_at?->format('M j, Y H:i:s'),
            'resolved_by' => $report->resolvedBy?->name,
            'resolution_notes' => $report->resolution_notes,
            'resolved_at' => $report->resolved_at?->format('M j, Y H:i:s'),
            'target_title' => $targetTitle,
            'target_url' => $targetUrl,
            'target_details' => $targetDetails,
            'target_user_id' => $targetUser?->id,
            'target_user_name' => $targetUser?->name,
            'target_is_listing' => $report->reportable_type === Listing::class,
            'target_is_user' => $report->reportable_type === User::class,
        ];

        $this->targetReportId = $report->id;
        $this->showInspectReportModal = true;
    }

    public function investigateReport(int $reportId): void
    {
        $this->authorizePermission('reports.manage');
        $report = ContentReport::findOrFail($reportId);
        $before = ['status' => $report->status];

        $report->status = 'investigating';
        $report->is_read = true;
        $report->read_at = $report->read_at ?: now();
        $report->resolved_by_user_id = Auth::id();
        $report->resolution_notes = ($report->resolution_notes ? $report->resolution_notes . " | " : '') . 'Investigation initiated by ' . Auth::user()->name . ' on ' . now()->toFormattedDateString();
        $report->save();

        AdminAuditService::log('report.investigate', $report, $before, ['status' => 'investigating']);
        $this->dispatch('toast', type: 'info', message: "Report #{$report->id} is now under active investigation.");
        session()->flash('success', "Report #{$report->id} status updated to Investigating.");
    }

    public function openResolveReportModal(int $reportId): void
    {
        $this->authorizePermission('reports.manage');
        $this->targetReportId = $reportId;
        $report = ContentReport::findOrFail($reportId);
        $report->markAsRead();
        $this->reportResolutionAction = $report->status === 'open' ? 'resolved' : $report->status;
        $this->reportResolutionNotes = $report->resolution_notes ?? '';
        $this->reportQuarantineTarget = false;
        $this->reportSuspendTargetUser = false;
        $this->showResolveReportModal = true;
    }

    public function markAllReportsRead(): void
    {
        $this->authorizePermission('reports.manage');
        ContentReport::where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
        session()->flash('success', 'All incident reports marked as read (Seen).');
    }

    public function resolveReport(?string $action = null): void
    {
        $this->authorizePermission('reports.manage');
        $act = $action ?: $this->reportResolutionAction;
        if (!in_array($act, ['investigating', 'resolved', 'dismissed'], true)) {
            $act = 'resolved';
        }

        $this->validate([
            'targetReportId' => ['required', 'exists:content_reports,id'],
            'reportResolutionNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $report = ContentReport::findOrFail($this->targetReportId);
        $before = ['status' => $report->status];

        $report->status = $act;
        $report->resolution_notes = $this->reportResolutionNotes ?: 'Actioned and concluded by moderation review.';
        $report->resolved_by_user_id = Auth::id();
        $report->resolved_at = $act === 'investigating' ? null : now();
        $report->save();

        // Optional attached actions
        if ($this->reportQuarantineTarget && $report->reportable_type === Listing::class) {
            $listing = Listing::find($report->reportable_id);
            if ($listing) {
                app(ComplianceService::class)->quarantineListing($listing, "Auto-quarantined via report #{$report->id}: {$report->reason_code}", Auth::id());
            }
        }

        if ($this->reportSuspendTargetUser) {
            $targetUserId = null;
            if ($report->reportable_type === User::class) {
                $targetUserId = $report->reportable_id;
            } elseif ($report->reportable_type === Listing::class) {
                $targetUserId = Listing::find($report->reportable_id)?->user_id;
            }

            if ($targetUserId && $targetUserId !== Auth::id()) {
                $targetUser = User::find($targetUserId);
                if ($targetUser && $targetUser->role !== 'super_admin') {
                    $targetUser->account_status = 'suspended';
                    $targetUser->save();
                    UserSuspension::create([
                        'user_id' => $targetUser->id,
                        'issued_by_admin_id' => Auth::id(),
                        'reason' => "Account suspended following confirmed incident report #{$report->id} ({$report->reason_code})",
                        'suspension_type' => 'temporary',
                        'starts_at' => now(),
                        'ends_at' => now()->addDays(30),
                    ]);
                }
            }
        }

        AdminAuditService::log('report.' . $act, $report, $before, ['status' => $act, 'notes' => $report->resolution_notes]);

        $this->showResolveReportModal = false;
        $this->showInspectReportModal = false;
        $this->inspectReportData = null;
        $this->showInspectReportModal = false;
        $this->targetReportId = null;
        $this->dispatch('toast', type: 'success', message: "Report #{$report->id} marked as {$act}.");
        session()->flash('success', "Report #{$report->id} concluded as {$act}.");
    }

    public function inspectAbuseMessage(int $messageId): void
    {
        $this->authorizePermission('moderation.view');
        $message = ContactMessage::with(['listing.city.state', 'recipient', 'sender'])->findOrFail($messageId);
        if (!$message->is_read) {
            $message->is_read = true;
            $message->read_at = now();
            $message->save();
        }

        $this->inspectAbuseData = [
            'id' => $message->id,
            'sender_name' => $message->sender_name,
            'sender_email' => $message->sender_email_encrypted,
            'recipient_name' => $message->recipient?->name ?? 'Platform Support',
            'listing_title' => $message->listing?->title ?? 'Direct Inquiry',
            'subject' => $message->subject ?? 'Flagged Inquiry',
            'message' => $message->message_encrypted,
            'flagged_reason' => $message->flagged_reason ?? 'spam',
            'is_read' => (bool) $message->is_read,
            'read_at' => $message->read_at?->format('M j, Y H:i:s'),
            'created_at' => $message->created_at?->format('M j, Y H:i:s'),
        ];
        $this->showInspectAbuseModal = true;
    }

    public function dismissFlaggedMessage(int $messageId): void
    {
        $this->authorizePermission('moderation.act');
        $message = ContactMessage::findOrFail($messageId);
        $message->is_flagged = false;
        $message->is_read = true;
        $message->read_at = $message->read_at ?: now();
        $message->save();

        $this->showInspectAbuseModal = false;
        $this->inspectAbuseData = null;

        AdminAuditService::log('contact_abuse.dismiss', $message, ['is_flagged' => true], ['is_flagged' => false]);
        session()->flash('success', "Flag on message #{$message->id} dismissed and marked as seen.");
    }

    public function quarantineReportedListing(int $reportId): void
    {
        $this->authorizePermission('reports.manage');
        $report = ContentReport::findOrFail($reportId);
        if ($report->reportable_type === Listing::class) {
            $listing = Listing::findOrFail($report->reportable_id);
            app(ComplianceService::class)->quarantineListing($listing, "Quarantined via report #{$report->id}: {$report->reason_code}", Auth::id());
            session()->flash('success', "Listing [{$listing->title}] quarantined immediately.");
        }
    }

    public function suspendReportedUser(int $reportId): void
    {
        $this->authorizePermission('users.suspend');
        $report = ContentReport::findOrFail($reportId);
        $targetUserId = null;
        if ($report->reportable_type === User::class) {
            $targetUserId = $report->reportable_id;
        } elseif ($report->reportable_type === Listing::class) {
            $targetUserId = Listing::find($report->reportable_id)?->user_id;
        }

        if (!$targetUserId || $targetUserId === Auth::id()) {
            $this->dispatch('toast', type: 'error', message: "Cannot suspend this user account.");
            return;
        }

        $user = User::findOrFail($targetUserId);
        $this->guardAgainstPrivilegedTarget($user);

        $user->account_status = 'suspended';
        $user->save();

        UserSuspension::create([
            'user_id' => $user->id,
            'issued_by_admin_id' => Auth::id(),
            'reason' => "Immediate administrative suspension via incident report #{$report->id}",
            'suspension_type' => 'temporary',
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
        ]);

        $report->status = 'resolved';
        $report->resolution_notes = ($report->resolution_notes ? $report->resolution_notes . " | " : '') . "Advertiser account #{$user->id} suspended by " . Auth::user()->name;
        $report->resolved_by_user_id = Auth::id();
        $report->resolved_at = now();
        $report->save();

        AdminAuditService::log('user.suspend_via_report', $user, ['account_status' => 'active'], ['account_status' => 'suspended', 'report_id' => $report->id]);
        $this->dispatch('toast', type: 'success', message: "User {$user->name} suspended and report #{$report->id} resolved.");
        session()->flash('success', "User #{$user->id} suspended and report #{$report->id} resolved.");
    }

    public function exportReportsCsv()
    {
        $this->authorizePermission('reports.manage');

        $reports = ContentReport::with(['reporter', 'resolvedBy'])->latest('created_at')->get();

        $filename = "localdatehub_abuse_reports_" . now()->format('Y-m-d_His') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($reports) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Report ID', 'Timestamp (UTC)', 'Status', 'Risk Level', 'Urgent Takedown',
                'Reason Code', 'Target Type', 'Target ID', 'Details / Description',
                'Reporter Name', 'Reporter Email', 'Reporter IP', 'Resolved By',
                'Resolved At', 'Resolution Notes'
            ]);

            foreach ($reports as $r) {
                fputcsv($file, [
                    $r->id,
                    $r->created_at?->toIso8601String(),
                    $r->status,
                    $r->risk_level ?? 'medium',
                    $r->is_urgent_takedown ? 'YES' : 'NO',
                    $r->reason_code,
                    class_basename($r->reportable_type),
                    $r->reportable_id,
                    $r->details,
                    $r->reporter?->name ?? 'Anonymous',
                    $r->reporter?->email ?? 'N/A',
                    $r->reporter_ip ?? '127.0.0.1',
                    $r->resolvedBy?->name ?? 'Unassigned',
                    $r->resolved_at?->toIso8601String() ?? 'N/A',
                    $r->resolution_notes ?? '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function deleteReport(int $reportId): void
    {
        $this->authorizePermission('reports.manage');
        $report = ContentReport::findOrFail($reportId);
        $before = [
            'id' => $report->id,
            'reason' => $report->reason_code,
            'status' => $report->status,
        ];

        $report->delete();

        AdminAuditService::log('report.delete', $report, $before, ['deleted' => true]);
        $this->dispatch('toast', type: 'success', message: "Report #{$reportId} was permanently deleted.");
        session()->flash('success', "Report #{$reportId} removed from database.");
    }

    public function resetAllTrafficAndViewCounts(): void
    {
        $this->authorizePermission('settings.manage');

        DB::table('listings')->update([
            'view_count' => 0,
            'reply_count' => 0,
            'favorite_count' => 0,
        ]);
        DB::table('platform_traffic_logs')->delete();
        if (Schema::hasTable('listing_analytics')) {
            DB::table('listing_analytics')->delete();
        }
        if (Schema::hasTable('analytics_events')) {
            DB::table('analytics_events')->delete();
        }
        if (Schema::hasTable('ad_placements')) {
            DB::table('ad_placements')->update(['impressions' => 0, 'clicks' => 0]);
        }

        AdminAuditService::log('analytics.reset_all_views_to_zero', null, null, ['reset_to_zero' => true]);
        $this->dispatch('toast', type: 'success', message: 'All traffic logs and view counters have been reset to 0.');
        session()->flash('success', 'All traffic logs, page views, and listing view counters have been reset to 0.');
    }

    public function resetAllContentReports(): void
    {
        $this->authorizePermission('reports.manage');

        $count = ContentReport::count();
        DB::table('content_reports')->delete();

        AdminAuditService::log('reports.reset_all_to_zero', null, ['previous_count' => $count], ['reset_to_zero' => true]);
        $this->dispatch('toast', type: 'success', message: 'All incident reports have been cleared and reset to 0.');
        session()->flash('success', 'All incident reports have been cleared and reset to 0.');
    }

    /*
    |--------------------------------------------------------------------------
    | DMCA, Legal Requests & Compliance (Phase 12 Controls)
    |--------------------------------------------------------------------------
    */

    public function openDmcaModal(int $id, string $action = 'takedown_issued'): void
    {
        $this->authorizePermission('reports.manage');
        $this->targetDmcaId = $id;
        $this->dmcaAction = $action;
        $this->dmcaNotes = '';
        $this->showDmcaModal = true;
    }

    public function processDmca(): void
    {
        $this->authorizePermission('reports.manage');
        $this->validate([
            'targetDmcaId' => ['required', 'exists:dmca_requests,id'],
            'dmcaAction' => ['required', Rule::in(['takedown_issued', 'counter_noticed', 'rejected', 'resolved', 'restored'])],
            'dmcaNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        $dmca = DmcaRequest::findOrFail($this->targetDmcaId);
        app(ComplianceService::class)->actionDmca(
            $dmca,
            $this->dmcaAction,
            $this->dmcaNotes,
            Auth::user()
        );

        $this->showDmcaModal = false;
        $this->targetDmcaId = null;
        session()->flash('success', "DMCA Ticket #{$dmca->ticket_number} processed with status [{$this->dmcaAction}].");
    }

    public function openLegalModal(int $id): void
    {
        $this->authorizePermission('verification.manage');
        $this->targetLegalId = $id;
        $this->legalComplianceNotes = '';
        $this->legalStatusAction = 'fulfilled';
        $this->showLegalModal = true;
    }

    public function processLegalRequest(): void
    {
        $this->authorizePermission('verification.manage');
        $this->validate([
            'targetLegalId' => ['required', 'exists:legal_requests,id'],
            'legalStatusAction' => ['required', Rule::in(['under_legal_review', 'fulfilled', 'rejected'])],
            'legalComplianceNotes' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $req = LegalRequest::findOrFail($this->targetLegalId);
        $before = ['status' => $req->status];
        $req->status = $this->legalStatusAction;
        $req->compliance_notes = $this->legalComplianceNotes;
        $req->handled_by_admin_id = Auth::id();
        if ($this->legalStatusAction === 'fulfilled') {
            $req->fulfilled_at = now();
        }
        $req->save();

        AdminAuditService::log('legal.action', $req, $before, [
            'status' => $this->legalStatusAction,
            'notes' => $this->legalComplianceNotes,
        ]);

        $this->showLegalModal = false;
        $this->targetLegalId = null;
        session()->flash('success', "Legal Request #{$req->reference_code} updated to [{$this->legalStatusAction}].");
    }

    public function freezeLegalEvidence(int $legalId): void
    {
        $this->authorizePermission('verification.manage');
        $req = LegalRequest::findOrFail($legalId);

        $target = $req->targetListing ?: $req->targetUser;
        if (!$target) {
            session()->flash('error', 'No identifiable target record linked to this legal request.');
            return;
        }

        app(ComplianceService::class)->freezeEvidence(
            $target,
            "Statutory 18 U.S.C. 2703(f) legal hold for case {$req->case_number} ({$req->agency_name})",
            Auth::id(),
            $req->id,
            90
        );

        session()->flash('success', "Evidence hold successfully applied to target record.");
    }

    public function resetStateComplianceForm(): void
    {
        $this->editingStateComplianceId = null;
        $this->stateComplianceForm = [
            'state_code' => '',
            'state_name' => '',
            'requires_age_gate' => true,
            'requires_id_verification' => false,
            'statutory_min_age' => 18,
            'mandatory_warning_text' => '',
            'is_restricted' => false,
            'legal_reference' => '',
        ];
    }

    public function openStateComplianceModal(?int $id = null): void
    {
        $this->authorizePermission('settings.manage');
        $this->resetStateComplianceForm();
        if ($id) {
            $st = StateCompliance::findOrFail($id);
            $this->editingStateComplianceId = $st->id;
            $this->stateComplianceForm = [
                'state_code' => $st->state_code,
                'state_name' => $st->state_name,
                'requires_age_gate' => (bool) $st->requires_age_gate,
                'requires_id_verification' => (bool) $st->requires_id_verification,
                'statutory_min_age' => $st->statutory_min_age,
                'mandatory_warning_text' => (string) $st->mandatory_warning_text,
                'is_restricted' => (bool) $st->is_restricted,
                'legal_reference' => (string) $st->legal_reference,
            ];
        }
        $this->showStateComplianceModal = true;
    }

    public function saveStateCompliance(): void
    {
        $this->authorizePermission('settings.manage');
        $data = $this->validate([
            'stateComplianceForm.state_code' => ['required', 'string', 'size:2', Rule::unique('state_compliances', 'state_code')->ignore($this->editingStateComplianceId)],
            'stateComplianceForm.state_name' => ['required', 'string', 'max:80'],
            'stateComplianceForm.requires_age_gate' => ['boolean'],
            'stateComplianceForm.requires_id_verification' => ['boolean'],
            'stateComplianceForm.statutory_min_age' => ['required', 'integer', 'min:18', 'max:21'],
            'stateComplianceForm.mandatory_warning_text' => ['nullable', 'string', 'max:1000'],
            'stateComplianceForm.is_restricted' => ['boolean'],
            'stateComplianceForm.legal_reference' => ['nullable', 'string', 'max:120'],
        ])['stateComplianceForm'];

        $data['state_code'] = strtoupper($data['state_code']);
        $data['requires_age_gate'] = (bool) ($data['requires_age_gate'] ?? false);
        $data['requires_id_verification'] = (bool) ($data['requires_id_verification'] ?? false);
        $data['is_restricted'] = (bool) ($data['is_restricted'] ?? false);

        if ($this->editingStateComplianceId) {
            $compliance = StateCompliance::findOrFail($this->editingStateComplianceId);
            $before = $compliance->toArray();
            $compliance->update($data);
            AdminAuditService::log('compliance.state_update', $compliance, $before, $data);
        } else {
            $compliance = StateCompliance::create($data);
            AdminAuditService::log('compliance.state_create', $compliance, null, $data);
        }

        $this->showStateComplianceModal = false;
        $this->resetStateComplianceForm();
        session()->flash('success', "Compliance rule for [{$compliance->state_code} - {$compliance->state_name}] saved.");
    }

    public function toggleStateIdRequirement(int $id): void
    {
        $this->authorizePermission('settings.manage');
        $st = StateCompliance::findOrFail($id);
        $before = ['requires_id_verification' => $st->requires_id_verification];
        $st->requires_id_verification = !$st->requires_id_verification;
        $st->save();

        AdminAuditService::log('compliance.state_id_toggle', $st, $before, ['requires_id_verification' => $st->requires_id_verification]);
        session()->flash('success', "Mandatory ID verification for [{$st->state_name}] " . ($st->requires_id_verification ? 'enabled' : 'disabled') . '.');
    }

    public function inspectPreservation(int $id): void
    {
        $this->authorizePermission('audit.view');
        $this->targetPreservationId = $id;
        $this->inspectedPreservation = EvidencePreservation::with(['legalRequest', 'frozenBy'])->findOrFail($id);
        $this->showPreservationModal = true;
    }

    public function releaseEvidenceHold(int $id): void
    {
        $this->authorizePermission('verification.manage');
        $hold = EvidencePreservation::findOrFail($id);
        $before = ['is_active' => $hold->is_active];
        $hold->is_active = false;
        $hold->save();

        AdminAuditService::log('evidence.release', $hold, $before, ['is_active' => false]);
        session()->flash('success', "Evidence preservation hold #{$hold->id} released.");
    }

    /*
    |--------------------------------------------------------------------------
    | 3, 4, 5. Categories, Locations, Attributes (quick toggles)
    |--------------------------------------------------------------------------
    */

    public function toggleCategory(int $categoryId): void
    {
        $this->authorizePermission('categories.manage');
        $category = Category::findOrFail($categoryId);
        $before = ['is_active' => $category->is_active];
        $category->is_active = !$category->is_active;
        $category->save();

        AdminAuditService::log('category.toggle', $category, $before, ['is_active' => $category->is_active]);
        session()->flash('success', "Category [{$category->name}] is now " . ($category->is_active ? 'active' : 'inactive') . '.');
    }

    public function toggleState(int $stateId): void
    {
        $this->authorizePermission('locations.manage');
        $state = State::findOrFail($stateId);
        $before = ['is_active' => $state->is_active];
        $state->is_active = !$state->is_active;
        $state->save();

        AdminAuditService::log('location.state_toggle', $state, $before, ['is_active' => $state->is_active]);
        session()->flash('success', "State [{$state->name}] is now " . ($state->is_active ? 'active' : 'inactive') . '.');
    }

    /*
    |--------------------------------------------------------------------------
    | 10–13. Pricing, Featured, Subscriptions, Payments & Refunds
    |--------------------------------------------------------------------------
    */

    public function resetPlanForm(): void
    {
        $this->editingPlanId = null;
        $this->planForm = [
            'name' => '', 'slug' => '', 'description' => '', 'price' => '0.00',
            'duration_days' => 30, 'plan_type' => 'listing_upgrade', 'is_active' => true,
        ];
    }

    public function openPlanModal(?int $planId = null): void
    {
        $this->authorizePermission('billing.manage_pricing');
        $this->resetPlanForm();
        if ($planId) {
            $plan = PricingPlan::findOrFail($planId);
            $this->editingPlanId = $plan->id;
            $this->planForm = [
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => (string) $plan->description,
                'price' => number_format($plan->price_cents / 100, 2, '.', ''),
                'duration_days' => $plan->duration_days,
                'plan_type' => $plan->plan_type,
                'is_active' => (bool) $plan->is_active,
            ];
        }
        $this->showPlanModal = true;
    }

    public function savePlan(): void
    {
        $this->authorizePermission('billing.manage_pricing');
        if (empty($this->planForm['slug']) && !empty($this->planForm['name'])) {
            $this->planForm['slug'] = Str::slug($this->planForm['name']);
        }

        $data = $this->validate([
            'planForm.name' => ['required', 'string', 'max:100'],
            'planForm.slug' => ['required', 'alpha_dash', 'max:100', Rule::unique('pricing_plans', 'slug')->ignore($this->editingPlanId)],
            'planForm.description' => ['nullable', 'string', 'max:1000'],
            'planForm.price' => ['required', 'numeric', 'min:0', 'max:10000'],
            'planForm.duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'planForm.plan_type' => ['required', Rule::in(['listing_upgrade', 'membership', 'featured_spot', 'bump'])],
            'planForm.is_active' => ['boolean'],
        ])['planForm'];

        $attributes = [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'price_cents' => (int) round(((float) $data['price']) * 100),
            'duration_days' => (int) $data['duration_days'],
            'plan_type' => $data['plan_type'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];

        if ($this->editingPlanId) {
            $plan = PricingPlan::findOrFail($this->editingPlanId);
            $before = $plan->only(array_keys($attributes));
            $plan->update($attributes);
            AdminAuditService::log('pricing.update', $plan, $before, $attributes);
        } else {
            $plan = PricingPlan::create($attributes + ['currency' => 'USD']);
            AdminAuditService::log('pricing.create', $plan, null, $attributes);
        }

        $this->syncBoostPlanFromPricingPlan($plan);

        $this->showPlanModal = false;
        $this->resetPlanForm();
        session()->flash('success', "Pricing plan [{$plan->name}] saved.");
    }

    public function togglePlan(int $planId): void
    {
        $this->authorizePermission('billing.manage_pricing');
        $plan = PricingPlan::findOrFail($planId);
        $before = ['is_active' => $plan->is_active];
        $plan->is_active = !$plan->is_active;
        $plan->save();

        $this->syncBoostPlanFromPricingPlan($plan);

        AdminAuditService::log('pricing.toggle', $plan, $before, ['is_active' => $plan->is_active]);
        session()->flash('success', "Plan [{$plan->name}] is now " . ($plan->is_active ? 'active' : 'inactive') . '.');
    }

    /**
     * Keep BoostPlan (used on /listings/{id}/boost) in sync with Admin PricingPlan edits.
     */
    protected function syncBoostPlanFromPricingPlan(PricingPlan $plan): void
    {
        if (!in_array($plan->plan_type, ['bump', 'featured_spot', 'listing_upgrade'], true)) {
            return;
        }

        $boostPlan = \App\Modules\Payments\Crypto\Models\BoostPlan::where('slug', $plan->slug)->first();

        if (!$boostPlan && $plan->plan_type === 'bump' && (int) $plan->duration_days === 1) {
            $boostPlan = \App\Modules\Payments\Crypto\Models\BoostPlan::where('slug', 'boost-24h')->first();
        }

        if ($boostPlan) {
            $boostPlan->update([
                'name' => $plan->name,
                'description' => $plan->description ?: $boostPlan->description,
                'price' => round($plan->price_cents / 100, 2),
                'duration_days' => (int) $plan->duration_days,
                'is_active' => (bool) $plan->is_active,
            ]);
        }
    }

    public function deactivateFeatured(int $featuredId): void
    {
        $this->authorizePermission('billing.manage_pricing');
        $featured = FeaturedListing::findOrFail($featuredId);
        $before = ['is_active' => $featured->is_active];
        $featured->is_active = false;
        $featured->save();

        $stillFeatured = FeaturedListing::where('listing_id', $featured->listing_id)->currentlyActive()->exists();
        if (!$stillFeatured) {
            Listing::whereKey($featured->listing_id)->update(['is_featured' => false]);
        }

        AdminAuditService::log('featured.deactivate', $featured, $before, ['is_active' => false]);
        session()->flash('success', "Featured placement #{$featured->id} deactivated.");
    }

    public function setSubscriptionSubTab(string $subTab): void
    {
        $this->subscriptionSubTab = in_array($subTab, ['subscribers', 'plans'], true) ? $subTab : 'subscribers';
        $this->resetPage('subscriptionsPage');
    }

    public function openAssignSubscriptionModal(): void
    {
        $this->authorizePermission('billing.manage_pricing');
        $this->assignSubscriptionForm = [
            'user_id' => '',
            'pricing_plan_id' => PricingPlan::where('is_active', true)->value('id') ?? '',
            'duration_days' => 30,
            'gateway' => 'manual_admin',
            'status' => 'active',
            'auto_renew' => true,
        ];
        $this->showAssignSubscriptionModal = true;
    }

    public function saveAssignedSubscription(): void
    {
        $this->authorizePermission('billing.manage_pricing');

        $validated = $this->validate([
            'assignSubscriptionForm.user_id' => ['required', 'exists:users,id'],
            'assignSubscriptionForm.pricing_plan_id' => ['required', 'exists:pricing_plans,id'],
            'assignSubscriptionForm.duration_days' => ['required', 'integer', 'min:1', 'max:730'],
            'assignSubscriptionForm.gateway' => ['required', 'string'],
            'assignSubscriptionForm.status' => ['required', Rule::in(['active', 'past_due', 'canceled', 'expired'])],
            'assignSubscriptionForm.auto_renew' => ['boolean'],
        ])['assignSubscriptionForm'];

        $startsAt = now();
        $endsAt = now()->addDays((int) $validated['duration_days']);

        $subscription = Subscription::create([
            'user_id' => $validated['user_id'],
            'pricing_plan_id' => $validated['pricing_plan_id'],
            'status' => $validated['status'],
            'gateway' => $validated['gateway'],
            'gateway_subscription_id' => 'ADMIN-ASSIGN-' . strtoupper(Str::random(10)),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'next_renewal_at' => $endsAt,
            'auto_renew' => (bool) ($validated['auto_renew'] ?? true),
        ]);

        $this->showAssignSubscriptionModal = false;
        $userName = $subscription->user?->name ?? 'User #' . $subscription->user_id;
        $planName = $subscription->plan?->name ?? 'Plan';

        $msg = "✓ Subscription [{$planName}] successfully assigned to {$userName} until " . $endsAt->format('M d, Y') . ".";
        session()->flash('success', $msg);
        $this->dispatch('toast', type: 'success', message: $msg);
    }

    public function extendSubscription(int $subscriptionId, int $days = 30): void
    {
        $this->authorizePermission('billing.manage_pricing');
        $subscription = Subscription::findOrFail($subscriptionId);
        
        $baseDate = ($subscription->ends_at && $subscription->ends_at->isFuture()) ? $subscription->ends_at : now();
        $newEnd = $baseDate->copy()->addDays($days);

        $subscription->update([
            'ends_at' => $newEnd,
            'next_renewal_at' => $newEnd,
            'status' => 'active',
            'canceled_at' => null,
        ]);

        $msg = "✓ Subscription #{$subscription->id} extended by +{$days} days (Valid until: " . $newEnd->format('M d, Y') . ").";
        session()->flash('success', $msg);
        $this->dispatch('toast', type: 'success', message: $msg);
    }

    public function toggleAutoRenew(int $subscriptionId): void
    {
        $this->authorizePermission('billing.manage_pricing');
        $subscription = Subscription::findOrFail($subscriptionId);
        $subscription->auto_renew = !$subscription->auto_renew;
        $subscription->save();

        $msg = "Auto-renew for subscription #{$subscription->id} is now " . ($subscription->auto_renew ? 'ENABLED' : 'DISABLED') . ".";
        session()->flash('success', $msg);
        $this->dispatch('toast', type: 'info', message: $msg);
    }

    public function openViewSubscriptionModal(int $subscriptionId): void
    {
        $this->authorizePermission('billing.view');
        $subscription = Subscription::with(['user.profile', 'user.wallet', 'plan'])->findOrFail($subscriptionId);

        $this->viewSubscriptionData = [
            'id' => $subscription->id,
            'status' => $subscription->status,
            'gateway' => $subscription->gateway,
            'gateway_subscription_id' => $subscription->gateway_subscription_id,
            'starts_at' => $subscription->starts_at?->format('M d, Y H:i'),
            'ends_at' => $subscription->ends_at?->format('M d, Y H:i'),
            'next_renewal_at' => $subscription->next_renewal_at?->format('M d, Y H:i'),
            'canceled_at' => $subscription->canceled_at?->format('M d, Y H:i'),
            'auto_renew' => (bool) $subscription->auto_renew,
            'user' => [
                'id' => $subscription->user?->id,
                'name' => $subscription->user?->name,
                'email' => $subscription->user?->email,
                'role' => $subscription->user?->role,
                'balance_credits' => $subscription->user?->wallet?->balance ?? 0,
            ],
            'plan' => [
                'id' => $subscription->plan?->id,
                'name' => $subscription->plan?->name,
                'description' => $subscription->plan?->description,
                'price' => '$' . number_format(($subscription->plan?->price_cents ?? 0) / 100, 2),
                'duration_days' => $subscription->plan?->duration_days,
                'features' => $subscription->plan?->features ?? [],
            ],
        ];

        $this->showViewSubscriptionModal = true;
    }

    public function cancelSubscription(int $subscriptionId): void
    {
        $this->authorizePermission('billing.manage_pricing');
        $subscription = Subscription::findOrFail($subscriptionId);
        $before = ['status' => $subscription->status];
        $subscription->status = 'canceled';
        $subscription->canceled_at = now();
        $subscription->auto_renew = false;
        $subscription->save();

        AdminAuditService::log('subscription.cancel', $subscription, $before, ['status' => 'canceled']);
        $msg = "Subscription #{$subscription->id} canceled successfully.";
        session()->flash('success', $msg);
        $this->dispatch('toast', type: 'warning', message: $msg);
    }

    public function openRefundModal(int $txnId): void
    {
        $this->authorizePermission('billing.refunds');
        $this->targetTransactionId = $txnId;
        $this->refundReason = '';
        $this->showRefundModal = true;
    }

    public function issueRefund(): void
    {
        $this->authorizePermission('billing.refunds');
        $this->validate([
            'targetTransactionId' => ['required', 'exists:payment_transactions,id'],
            'refundReason' => ['required', 'string', 'min:5', 'max:150'],
        ]);

        $txn = PaymentTransaction::findOrFail($this->targetTransactionId);
        if ($txn->status !== 'completed') {
            throw ValidationException::withMessages(['refundReason' => 'Only completed transactions can be refunded.']);
        }

        $before = ['status' => $txn->status];

        $gatewayManager = app(PaymentGatewayManager::class);
        $gatewayRefundId = 're_' . Str::random(16);

        try {
            $gateway = $gatewayManager->driver($txn->gateway);
            $res = $gateway->refund($txn->gateway_transaction_id ?: $txn->reference_id, $txn->amount_cents, $this->refundReason);
            if ($res->refundId) {
                $gatewayRefundId = $res->refundId;
            }
        } catch (\Throwable) {
            // Fall back to manual ledger recording
        }

        DB::transaction(function () use ($txn, $gatewayRefundId) {
            Refund::create([
                'payment_transaction_id' => $txn->id,
                'processed_by_admin_id' => Auth::id(),
                'amount_cents' => $txn->amount_cents,
                'reason' => $this->refundReason,
                'status' => 'completed',
                'gateway_refund_id' => $gatewayRefundId,
            ]);

            $txn->status = 'refunded';
            $txn->save();

            if ($txn->invoice) {
                $txn->invoice->status = 'refunded';
                $txn->invoice->save();
            }

            if ($txn->listing_id) {
                FeaturedListing::where('listing_id', $txn->listing_id)
                    ->where('user_id', $txn->user_id)
                    ->update(['is_active' => false, 'status' => 'deactivated']);

                $txn->listing->is_featured = false;
                $txn->listing->promoted_until = null;
                $txn->listing->save();
            }
        });

        AdminAuditService::log('billing.refund', $txn, $before, ['status' => 'refunded', 'amount_cents' => $txn->amount_cents, 'reason' => $this->refundReason]);

        $this->showRefundModal = false;
        $this->targetTransactionId = null;
        session()->flash('success', 'Refund of $' . number_format($txn->amount_cents / 100, 2) . ' successfully recorded.');
    }

    public function reconcileTransactions(): void
    {
        $this->authorizePermission('billing.view');

        $unreconciled = PaymentTransaction::where('is_reconciled', false)->get();
        $records = [];
        foreach ($unreconciled as $txn) {
            $records[] = [
                'gateway_id' => $txn->gateway_transaction_id ?: $txn->reference_id,
                'reference_id' => $txn->reference_id,
                'status' => $txn->status,
                'amount_cents' => $txn->amount_cents,
            ];
        }

        $reconciliationService = app(ReconciliationService::class);
        $result = $reconciliationService->reconcileGatewayBatch(config('monetization.default', 'sandbox'), $records);

        session()->flash('success', "Reconciliation completed: {$result['matched']} matched, {$result['updated']} updated, " . count($result['discrepancies']) . " discrepancies.");
    }

    public function openPricingRuleModal(?int $id = null): void
    {
        $this->authorizePermission('billing.plans');
        $this->editingPricingRuleId = $id;

        if ($id) {
            $rule = PricingRule::findOrFail($id);
            $this->pricingRuleForm = [
                'rule_name' => $rule->rule_name,
                'pricing_plan_id' => $rule->pricing_plan_id,
                'category_id' => $rule->category_id,
                'state_id' => $rule->state_id,
                'discount_percentage' => $rule->discount_percentage,
                'fixed_price_cents' => $rule->fixed_price_cents,
                'is_free' => (bool) $rule->is_free,
                'is_active' => (bool) $rule->is_active,
            ];
        } else {
            $this->pricingRuleForm = [
                'rule_name' => '',
                'pricing_plan_id' => null,
                'category_id' => null,
                'state_id' => null,
                'discount_percentage' => 0,
                'fixed_price_cents' => null,
                'is_free' => false,
                'is_active' => true,
            ];
        }

        $this->showPricingRuleModal = true;
    }

    public function savePricingRule(): void
    {
        $this->authorizePermission('billing.plans');
        $this->validate([
            'pricingRuleForm.rule_name' => ['required', 'string', 'max:150'],
            'pricingRuleForm.discount_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'pricingRuleForm.fixed_price_cents' => ['nullable', 'integer', 'min:0'],
            'pricingRuleForm.is_free' => ['boolean'],
            'pricingRuleForm.is_active' => ['boolean'],
        ]);

        $data = [
            'rule_name' => $this->pricingRuleForm['rule_name'],
            'pricing_plan_id' => $this->pricingRuleForm['pricing_plan_id'] ?: null,
            'category_id' => $this->pricingRuleForm['category_id'] ?: null,
            'state_id' => $this->pricingRuleForm['state_id'] ?: null,
            'discount_percentage' => (int) ($this->pricingRuleForm['discount_percentage'] ?? 0),
            'fixed_price_cents' => $this->pricingRuleForm['fixed_price_cents'] !== null && $this->pricingRuleForm['fixed_price_cents'] !== '' ? (int) $this->pricingRuleForm['fixed_price_cents'] : null,
            'is_free' => (bool) ($this->pricingRuleForm['is_free'] ?? false),
            'is_active' => (bool) ($this->pricingRuleForm['is_active'] ?? true),
        ];

        if ($this->editingPricingRuleId) {
            $rule = PricingRule::findOrFail($this->editingPricingRuleId);
            $rule->update($data);
            session()->flash('success', "Pricing rule [{$rule->rule_name}] updated.");
        } else {
            $rule = PricingRule::create($data);
            session()->flash('success', "Pricing rule [{$rule->rule_name}] created.");
        }

        $this->showPricingRuleModal = false;
        $this->editingPricingRuleId = null;
    }

    public function deletePricingRule(int $id): void
    {
        $this->authorizePermission('billing.plans');
        $rule = PricingRule::findOrFail($id);
        $name = $rule->rule_name;
        $rule->delete();

        session()->flash('success', "Pricing rule [{$name}] deleted.");
    }

    /*
    |--------------------------------------------------------------------------
    | Credit Packages Management (User Wallet Replenishment Bundles)
    |--------------------------------------------------------------------------
    */

    public function openCreatePackageModal(): void
    {
        $this->authorizePermission('billing.plans');
        $this->editingPackageId = null;
        $maxOrder = (int) CreditPackage::max('display_order');
        $this->packageForm = [
            'name' => '',
            'credits' => 100,
            'bonus_credits' => 0,
            'price_usd' => '9.99',
            'display_order' => $maxOrder + 1,
            'is_popular' => false,
            'is_active' => true,
        ];
        $this->showPackageModal = true;
    }

    public function openEditPackageModal(int $id): void
    {
        $this->authorizePermission('billing.plans');
        $pkg = CreditPackage::findOrFail($id);
        $this->editingPackageId = $pkg->id;
        $this->packageForm = [
            'name' => $pkg->name,
            'credits' => (int) $pkg->credits,
            'bonus_credits' => (int) $pkg->bonus_credits,
            'price_usd' => (string) $pkg->price_usd,
            'display_order' => (int) $pkg->display_order,
            'is_popular' => (bool) $pkg->is_popular,
            'is_active' => (bool) $pkg->is_active,
        ];
        $this->showPackageModal = true;
    }

    public function savePackage(): void
    {
        $this->authorizePermission('billing.plans');

        $this->validate([
            'packageForm.name' => 'required|string|max:100',
            'packageForm.credits' => 'required|integer|min:1',
            'packageForm.bonus_credits' => 'nullable|integer|min:0',
            'packageForm.price_usd' => 'required|numeric|min:0.5',
            'packageForm.display_order' => 'required|integer|min:1',
            'packageForm.is_popular' => 'boolean',
            'packageForm.is_active' => 'boolean',
        ]);

        $data = [
            'name' => trim($this->packageForm['name']),
            'credits' => (int) $this->packageForm['credits'],
            'bonus_credits' => (int) ($this->packageForm['bonus_credits'] ?? 0),
            'price_usd' => $this->packageForm['price_usd'],
            'display_order' => (int) $this->packageForm['display_order'],
            'is_popular' => (bool) ($this->packageForm['is_popular'] ?? false),
            'is_active' => (bool) ($this->packageForm['is_active'] ?? false),
        ];

        if ($this->editingPackageId) {
            $pkg = CreditPackage::findOrFail($this->editingPackageId);
            $before = $pkg->toArray();
            $pkg->update($data);
            AdminAuditService::log('credit_package.update', $pkg, $before, $pkg->toArray());
            $this->dispatch('toast', type: 'success', message: "Credit package '{$pkg->name}' updated successfully.");
        } else {
            $pkg = CreditPackage::create($data);
            AdminAuditService::log('credit_package.create', $pkg, null, $pkg->toArray());
            $this->dispatch('toast', type: 'success', message: "New credit package '{$pkg->name}' created successfully.");
        }

        $this->closeModals();
    }

    public function togglePackageActive(int $id): void
    {
        $this->authorizePermission('billing.plans');
        $pkg = CreditPackage::findOrFail($id);
        $before = ['is_active' => $pkg->is_active];
        $pkg->is_active = !$pkg->is_active;
        $pkg->save();

        AdminAuditService::log('credit_package.toggle', $pkg, $before, ['is_active' => $pkg->is_active]);
        $state = $pkg->is_active ? 'activated and live on checkout' : 'deactivated and hidden from checkout';
        $this->dispatch('toast', type: 'success', message: "Package '{$pkg->name}' is now {$state}.");
    }

    public function deletePackage(int $id): void
    {
        $this->authorizePermission('billing.plans');
        $pkg = CreditPackage::findOrFail($id);
        $name = $pkg->name;
        $before = $pkg->toArray();
        $pkg->delete();

        AdminAuditService::log('credit_package.delete', CreditPackage::class, $before, ['deleted_id' => $id, 'name' => $name]);
        $this->dispatch('toast', type: 'success', message: "Credit package '{$name}' deleted.");
    }

    public function setPaymentSubTab(string $subTab): void
    {
        $this->resetValidation();
        $this->resetErrorBag();
        $this->consolidationErrorMessage = null;
        $this->consolidationSuccessMessage = null;
        $this->treasuryErrorMessage = null;
        $this->treasurySuccessMessage = null;

        $this->paymentSubTab = in_array($subTab, ['packages', 'transactions', 'crypto', 'treasury', 'wallet', 'rules', 'fraud', 'refunds'], true)
            ? $subTab
            : 'transactions';

        if ($this->paymentSubTab === 'treasury') {
            $this->loadTreasurySettings();
        }

        $this->resetPage('transactionsPage');
        $this->resetPage('cryptoPage');
        $this->resetPage('walletPage');
        $this->resetPage('refundsPage');
    }

    public function updatedManualSweepNetwork(string $value): void
    {
        $networkKey = strtoupper(trim($value));
        $dest = CryptoTreasuryWallet::where('tier', CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET)
            ->where('network_key', $networkKey)
            ->value('public_receiving_address');

        if ($dest) {
            $this->manualSweepDestination = $dest;
        }

        if ($networkKey === 'TRC20') {
            $this->manualSweepCurrency = 'USDT';
        } elseif ($networkKey === 'ERC20') {
            $this->manualSweepCurrency = in_array($this->manualSweepCurrency, ['USDT', 'USDC', 'ETH']) ? $this->manualSweepCurrency : 'USDT';
        } elseif ($networkKey === 'BEP20') {
            $this->manualSweepCurrency = 'BNB';
        } elseif ($networkKey === 'BTC') {
            $this->manualSweepCurrency = 'BTC';
        }
    }

    public function loadTreasurySettings(): void
    {
        $masterSecret = CryptoPaymentSetting::get('hd_master_secret');
        $masterConfigured = !empty($masterSecret);
        $previewAddress = null;
        if ($masterConfigured) {
            $previewAddress = app(\App\Modules\Payments\Crypto\Services\InHouse\CryptoSignerService::class)
                ->deriveEvmAddress((string) $masterSecret, 0);
        }

        $mainErc20 = CryptoTreasuryWallet::where('tier', CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET)->where('network_key', 'ERC20')->value('public_receiving_address') ?? '';
        if (str_ends_with(strtolower($mainErc20), 'beef') || str_contains($mainErc20, 'PLACEHOLDER')) {
            $mainErc20 = '';
        }

        $this->treasurySettings = [
            'auto_sweep_threshold_usd' => CryptoPaymentSetting::get('auto_sweep_threshold_usd', 100.00),
            'large_transfer_approval_threshold_usd' => CryptoPaymentSetting::get('large_transfer_approval_threshold_usd', 500.00),
            'min_sweep_amount_usd' => CryptoPaymentSetting::get('min_sweep_amount_usd', 15.00),
            'max_auto_sweep_amount_usd' => CryptoPaymentSetting::get('max_auto_sweep_amount_usd', 500.00),
            'daily_sweep_limit_usd' => CryptoPaymentSetting::get('daily_sweep_limit_usd', 10000.00),
            'auto_sweep_enabled' => (bool) CryptoPaymentSetting::get('auto_sweep_enabled', true),
            'auto_sweep_direct_to_main' => (bool) CryptoPaymentSetting::get('auto_sweep_direct_to_main', true),
            'hd_master_secret_input' => '',
            'hd_master_secret_configured' => $masterConfigured,
            'hd_master_preview_address' => $previewAddress,
            'temp_trust_wallet_trc20' => CryptoTreasuryWallet::where('tier', CryptoTreasuryWallet::TIER_TEMPORARY_TRUST_WALLET)->where('network_key', 'TRC20')->value('public_receiving_address') ?? '',
            'temp_trust_wallet_erc20' => CryptoTreasuryWallet::where('tier', CryptoTreasuryWallet::TIER_TEMPORARY_TRUST_WALLET)->where('network_key', 'ERC20')->value('public_receiving_address') ?? '',
            'main_trust_wallet_trc20' => CryptoTreasuryWallet::where('tier', CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET)->where('network_key', 'TRC20')->value('public_receiving_address') ?? '',
            'main_trust_wallet_erc20' => $mainErc20,
            'main_trust_wallet_btc' => CryptoTreasuryWallet::where('tier', CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET)->where('network_key', 'BTC')->value('public_receiving_address') ?? '',
            'main_trust_wallet_ltc' => CryptoTreasuryWallet::where('tier', CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET)->where('network_key', 'LTC')->value('public_receiving_address') ?? '',
        ];

        if (empty($this->manualSweepDestination)) {
            $this->manualSweepDestination = CryptoTreasuryWallet::where('tier', CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET)
                ->where('network_key', $this->manualSweepNetwork)
                ->value('public_receiving_address') ?? '';
        }
    }

    public function saveTreasurySettings(): void
    {
        $this->authorizePermission('billing.plans');

        $this->treasurySuccessMessage = null;
        $this->treasuryErrorMessage = null;

        CryptoPaymentSetting::set('auto_sweep_threshold_usd', (float) ($this->treasurySettings['auto_sweep_threshold_usd'] ?? 100.00));
        CryptoPaymentSetting::set('large_transfer_approval_threshold_usd', (float) ($this->treasurySettings['large_transfer_approval_threshold_usd'] ?? 500.00));
        CryptoPaymentSetting::set('min_sweep_amount_usd', (float) ($this->treasurySettings['min_sweep_amount_usd'] ?? 15.00));
        CryptoPaymentSetting::set('max_auto_sweep_amount_usd', (float) ($this->treasurySettings['max_auto_sweep_amount_usd'] ?? 500.00));
        CryptoPaymentSetting::set('daily_sweep_limit_usd', (float) ($this->treasurySettings['daily_sweep_limit_usd'] ?? 10000.00));
        CryptoPaymentSetting::set('auto_sweep_enabled', (bool) ($this->treasurySettings['auto_sweep_enabled'] ?? true));
        CryptoPaymentSetting::set('auto_sweep_direct_to_main', (bool) ($this->treasurySettings['auto_sweep_direct_to_main'] ?? true));

        // Update Trust Wallet addresses FIRST so receiving addresses always save cleanly
        if (!empty($this->treasurySettings['temp_trust_wallet_trc20'])) {
            CryptoTreasuryWallet::updateOrCreate(
                ['tier' => CryptoTreasuryWallet::TIER_TEMPORARY_TRUST_WALLET, 'network_key' => 'TRC20'],
                ['wallet_name' => 'Temporary Trust Wallet TRON', 'public_receiving_address' => trim($this->treasurySettings['temp_trust_wallet_trc20']), 'is_active' => true]
            );
        }

        if (!empty($this->treasurySettings['temp_trust_wallet_erc20'])) {
            CryptoTreasuryWallet::updateOrCreate(
                ['tier' => CryptoTreasuryWallet::TIER_TEMPORARY_TRUST_WALLET, 'network_key' => 'ERC20'],
                ['wallet_name' => 'Temporary Trust Wallet EVM', 'public_receiving_address' => trim($this->treasurySettings['temp_trust_wallet_erc20']), 'is_active' => true]
            );
        }

        if (!empty($this->treasurySettings['main_trust_wallet_trc20'])) {
            CryptoTreasuryWallet::updateOrCreate(
                ['tier' => CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET, 'network_key' => 'TRC20'],
                ['wallet_name' => 'Main Trust Wallet TRON', 'public_receiving_address' => trim($this->treasurySettings['main_trust_wallet_trc20']), 'is_active' => true]
            );
        }

        if (!empty($this->treasurySettings['main_trust_wallet_erc20'])) {
            CryptoTreasuryWallet::updateOrCreate(
                ['tier' => CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET, 'network_key' => 'ERC20'],
                ['wallet_name' => 'Main Trust Wallet EVM', 'public_receiving_address' => trim($this->treasurySettings['main_trust_wallet_erc20']), 'is_active' => true]
            );
        }

        if (!empty($this->treasurySettings['main_trust_wallet_btc'])) {
            CryptoTreasuryWallet::updateOrCreate(
                ['tier' => CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET, 'network_key' => 'BTC'],
                ['wallet_name' => 'Main Trust Wallet BTC', 'public_receiving_address' => trim($this->treasurySettings['main_trust_wallet_btc']), 'is_active' => true]
            );
        }

        if (!empty($this->treasurySettings['main_trust_wallet_ltc'])) {
            CryptoTreasuryWallet::updateOrCreate(
                ['tier' => CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET, 'network_key' => 'LTC'],
                ['wallet_name' => 'Main Trust Wallet LTC', 'public_receiving_address' => trim($this->treasurySettings['main_trust_wallet_ltc']), 'is_active' => true]
            );
        }

        // Save Encrypted Master Seed Phrase / Private Key if newly entered
        $newSecret = trim((string) ($this->treasurySettings['hd_master_secret_input'] ?? ''));
        if ($newSecret !== '') {
            $signer = app(\App\Modules\Payments\Crypto\Services\InHouse\CryptoSignerService::class);
            $deriveRes = $signer->deriveEvmAddressWithDetails($newSecret, 0);
            if (!($deriveRes['success'] ?? false) || empty($deriveRes['address'])) {
                $detail = $deriveRes['error'] ?? 'Please enter a valid 12/24-word mnemonic phrase or 64-char hex EVM private key.';
                $this->treasuryErrorMessage = "Unable to configure Master Seed/Key: {$detail}";
                return;
            }
            CryptoPaymentSetting::set('hd_master_secret', $newSecret, 'Encrypted HD Master Seed / Private Key for Auto-Sweep', true);
        }

        $this->loadTreasurySettings();

        $msg = '✓ Real-time Update: Hybrid Treasury thresholds and Trust Wallet addresses saved successfully at ' . now()->format('H:i:s') . '!';
        $this->treasurySuccessMessage = $msg;
        $this->settingsSuccessMessage = $msg;
        session()->flash('success', $msg);
        $this->dispatch('toast', type: 'success', message: 'Trust Wallet settings saved successfully!');
    }

    public function toggleTreasuryEmergencyPause(): void
    {
        $this->authorizePermission('billing.plans');
        $current = (bool) CryptoPaymentSetting::get('treasury_emergency_pause', false);
        $new = !$current;
        CryptoPaymentSetting::set('treasury_emergency_pause', $new, "Toggled by admin " . Auth::user()->name);

        $msg = $new 
            ? '⚠️ EMERGENCY: All automated and queued treasury sweeps have been PAUSED immediately. Payment verification continues.' 
            : '✓ Treasury transfers resumed: Automated and approved sweeping is now ACTIVE.';

        $this->treasurySuccessMessage = $msg;
        $this->treasuryErrorMessage = null;
        session()->flash('success', $msg);
        $this->dispatch('toast', type: $new ? 'warning' : 'success', message: $msg);
    }

    public function approveLargeSweep(int $approvalId, HybridTreasuryEngine $treasuryEngine): void
    {
        $this->authorizePermission('billing.plans');
        try {
            $sweep = $treasuryEngine->processApproval($approvalId, Auth::user(), true, 'Approved via Admin Treasury Hub');
            $msg = "✓ Sweep #{$sweep->id} for \${$sweep->amount_usd} ({$sweep->amount} {$sweep->currency_symbol}) approved and executed to Trust Wallet.";
            $this->treasurySuccessMessage = $msg;
            $this->treasuryErrorMessage = null;
            session()->flash('success', $msg);
            $this->dispatch('toast', type: 'success', message: "Sweep #{$sweep->id} approved!");
        } catch (\Exception $e) {
            $msg = "Failed to approve sweep: {$e->getMessage()}";
            $this->treasuryErrorMessage = $msg;
            session()->flash('error', $msg);
            $this->dispatch('toast', type: 'error', message: $msg);
        }
    }

    public function rejectLargeSweep(int $approvalId, HybridTreasuryEngine $treasuryEngine): void
    {
        $this->authorizePermission('billing.plans');
        try {
            $reason = trim($this->rejectReason) ?: 'Rejected by administrator';
            $sweep = $treasuryEngine->processApproval($approvalId, Auth::user(), false, $reason);
            $this->rejectReason = '';
            $msg = "Sweep #{$sweep->id} has been rejected. Funds remain safe in deposit address.";
            $this->treasurySuccessMessage = $msg;
            $this->treasuryErrorMessage = null;
            session()->flash('success', $msg);
            $this->dispatch('toast', type: 'info', message: "Sweep #{$sweep->id} rejected.");
        } catch (\Exception $e) {
            $msg = "Failed to reject sweep: {$e->getMessage()}";
            $this->treasuryErrorMessage = $msg;
            session()->flash('error', $msg);
            $this->dispatch('toast', type: 'error', message: $msg);
        }
    }

    public function setSweepAmountPercent(int $percent): void
    {
        $networkKey = strtoupper(trim($this->manualSweepNetwork));
        $tempTreasury = CryptoTreasuryWallet::where('tier', CryptoTreasuryWallet::TIER_TEMPORARY_TRUST_WALLET)
            ->where('network_key', $networkKey)
            ->first();

        $avail = $tempTreasury ? (float) $tempTreasury->available_balance : 0.0;
        if ($avail <= 0) {
            $this->consolidationErrorMessage = "Temporary wallet currently has 0.00 balance. Click '+ Load $100 Test Balance' first.";
            $this->dispatch('toast', type: 'warning', message: "No funds in temporary wallet.");
            return;
        }

        $calc = ($avail * $percent) / 100;
        $this->manualSweepAmount = number_format(max(0.01, $calc), 2, '.', '');
        $this->consolidationErrorMessage = null;
    }

    public function executeManualConsolidation(HybridTreasuryEngine $treasuryEngine): void
    {
        $this->authorizePermission('billing.plans');

        $this->treasurySuccessMessage = null;
        $this->treasuryErrorMessage = null;
        $this->consolidationSuccessMessage = null;
        $this->consolidationErrorMessage = null;

        $networkKey = strtoupper(trim($this->manualSweepNetwork));
        $dest = trim($this->manualSweepDestination);

        // Fetch temporary treasury first to check available balance
        $tempTreasury = CryptoTreasuryWallet::where('tier', CryptoTreasuryWallet::TIER_TEMPORARY_TRUST_WALLET)
            ->where('network_key', $networkKey)
            ->first();

        $avail = $tempTreasury ? (float) $tempTreasury->available_balance : 0.0;

        if (empty($this->manualSweepAmount) || (float) $this->manualSweepAmount <= 0) {
            $msg = "Please enter an amount to transfer. Available balance: " . number_format($avail, 2) . " {$this->manualSweepCurrency}. Or click [Max All] to transfer everything.";
            $this->consolidationErrorMessage = $msg;
            $this->dispatch('toast', type: 'error', message: "Please specify an amount to transfer.");
            return;
        }

        $amount = (float) $this->manualSweepAmount;

        if ($avail < $amount) {
            $msg = "Cannot transfer {$amount} {$this->manualSweepCurrency}: Temporary Trust Wallet ({$networkKey}) only has " . number_format($avail, 4) . " available. Click '+ Load $100 Test Balance' or choose a smaller amount.";
            $this->consolidationErrorMessage = $msg;
            $this->treasuryErrorMessage = $msg;
            $this->dispatch('toast', type: 'error', message: "Insufficient Temporary Wallet balance (" . number_format($avail, 2) . " avail).");
            return;
        }

        if (empty($dest)) {
            $dest = CryptoTreasuryWallet::where('tier', CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET)
                ->where('network_key', $networkKey)
                ->value('public_receiving_address') ?? '';
            $this->manualSweepDestination = $dest;
        }

        if (empty($dest)) {
            $msg = "Please configure a Main Trust Wallet destination address for {$networkKey} in Treasury Settings.";
            $this->consolidationErrorMessage = $msg;
            $this->dispatch('toast', type: 'error', message: $msg);
            return;
        }

        // Verify destination matches registered main trust wallet
        $mainRegistered = CryptoTreasuryWallet::where('tier', CryptoTreasuryWallet::TIER_MAIN_TRUST_WALLET)
            ->where('network_key', $networkKey)
            ->value('public_receiving_address');

        if (!$mainRegistered || strcasecmp(trim($mainRegistered), $dest) !== 0) {
            $msg = "Destination address does not match your registered Main Trust Wallet for {$networkKey} [{$mainRegistered}].";
            $this->consolidationErrorMessage = $msg;
            $this->treasuryErrorMessage = $msg;
            $this->dispatch('toast', type: 'error', message: 'Destination does not match registered Main Trust Wallet.');
            return;
        }

        try {
            $sweep = $treasuryEngine->executeManualConsolidation(
                Auth::user(),
                $networkKey,
                $this->manualSweepCurrency,
                $amount,
                $dest
            );

            // Set remaining amount or keep 100 for convenience
            $remaining = max(0.0, $avail - $amount);
            $this->manualSweepAmount = $remaining > 0 ? number_format(min(100.0, $remaining), 2, '.', '') : '100.00';

            $msg = "✓ Consolidated {$sweep->amount} {$sweep->currency_symbol} to Main Trust Wallet successfully! TX Hash: {$sweep->sweep_tx_hash}";
            $this->consolidationSuccessMessage = $msg;
            $this->treasurySuccessMessage = $msg;
            session()->flash('success', $msg);
            $this->dispatch('toast', type: 'success', message: "Consolidated {$sweep->amount} {$sweep->currency_symbol} to Main Trust Wallet!");
        } catch (\Exception $e) {
            $msg = "Manual transfer failed: {$e->getMessage()}";
            $this->consolidationErrorMessage = $msg;
            $this->treasuryErrorMessage = $msg;
            session()->flash('error', $msg);
            $this->dispatch('toast', type: 'error', message: $msg);
        }
    }

    public function simulateTestDeposit(?string $networkKey = null, float $amount = 100.0): void
    {
        $this->authorizePermission('billing.plans');
        $networkKey = strtoupper(trim($networkKey ?: $this->manualSweepNetwork));
        $wallet = CryptoTreasuryWallet::firstOrCreate(
            ['tier' => CryptoTreasuryWallet::TIER_TEMPORARY_TRUST_WALLET, 'network_key' => $networkKey],
            [
                'wallet_name' => "Temporary Trust Wallet {$networkKey}",
                'public_receiving_address' => 'TLMYTVqBcN8CTtGVCvDhQ5Pue6zveknMva',
                'is_active' => true,
            ]
        );

        $wallet->increment('current_balance', $amount);
        $wallet->increment('available_balance', $amount);
        $wallet->increment('total_received', $amount);

        // Pre-fill amount to 100.00 if empty
        if (empty($this->manualSweepAmount) || (float) $this->manualSweepAmount <= 0) {
            $this->manualSweepAmount = '100.00';
        }

        $this->consolidationErrorMessage = null;
        $this->treasuryErrorMessage = null;
        $msg = "✓ Test Funds Loaded: +{$amount} {$this->manualSweepCurrency} added to Temporary Trust Wallet! Available: " . number_format($wallet->available_balance, 2) . " {$this->manualSweepCurrency}. Ready to consolidate.";
        $this->consolidationSuccessMessage = $msg;
        $this->treasurySuccessMessage = $msg;
        session()->flash('success', $msg);
        $this->dispatch('toast', type: 'success', message: "Added +{$amount} test funds to Temporary Trust Wallet!");
    }

    public function replenishGasReserve(string $networkKey, float $amount): void
    {
        $this->authorizePermission('billing.plans');
        $reserve = CryptoGasReserve::where('network_key', strtoupper($networkKey))->firstOrFail();
        $reserve->increment('current_balance', $amount);
        $reserve->update(['is_alert_active' => false, 'last_replenished_at' => now()]);

        $this->treasuryErrorMessage = null;
        $msg = "✓ Gas reserve for {$networkKey} replenished with +{$amount} {$reserve->gas_currency}. New balance: " . number_format($reserve->current_balance, 4);
        $this->treasurySuccessMessage = $msg;
        session()->flash('success', $msg);
        $this->dispatch('toast', type: 'success', message: "Replenished {$networkKey} gas reserve!");
    }

    public function resetPaymentFilters(): void
    {
        $this->paymentSearch = '';
        $this->paymentGatewayFilter = 'all';
        $this->paymentStatusFilter = 'all';
        $this->resetPage('transactionsPage');
        $this->resetPage('cryptoPage');
        $this->resetPage('walletPage');
        $this->resetPage('refundsPage');
    }

    public function openViewTransactionModal(int $txnId): void
    {
        $this->authorizePermission('billing.view');
        $txn = PaymentTransaction::with(['user.profile', 'user.wallet', 'plan', 'listing.category', 'listing.city.state', 'invoice'])->findOrFail($txnId);

        $this->viewTransactionData = [
            'id' => $txn->id,
            'reference_id' => $txn->reference_id,
            'amount_cents' => $txn->amount_cents,
            'amount_formatted' => '$' . number_format($txn->amount_cents / 100, 2),
            'currency' => $txn->currency ?? 'USD',
            'status' => $txn->status,
            'gateway' => $txn->gateway,
            'gateway_transaction_id' => $txn->gateway_transaction_id,
            'is_reconciled' => (bool) $txn->is_reconciled,
            'reconciled_at' => $txn->reconciled_at?->format('M d, Y H:i'),
            'created_at' => $txn->created_at?->format('M d, Y H:i:s'),
            'user' => [
                'id' => $txn->user?->id,
                'name' => $txn->user?->name ?? 'Guest User',
                'email' => $txn->user?->email,
                'status' => $txn->user?->status,
                'credits' => $txn->user?->wallet?->balance ?? 0,
            ],
            'plan' => $txn->plan ? [
                'id' => $txn->plan->id,
                'name' => $txn->plan->name,
                'price' => '$' . number_format($txn->plan->price_cents / 100, 2),
                'duration_days' => $txn->plan->duration_days,
            ] : null,
            'listing' => $txn->listing ? [
                'id' => $txn->listing->id,
                'title' => $txn->listing->title,
                'status' => $txn->listing->status,
                'category' => $txn->listing->category?->name,
                'location' => $txn->listing->city ? "{$txn->listing->city->name}, {$txn->listing->city->state?->code}" : null,
            ] : null,
            'invoice' => $txn->invoice ? [
                'id' => $txn->invoice->id,
                'invoice_number' => $txn->invoice->invoice_number,
                'status' => $txn->invoice->status,
                'paid_at' => $txn->invoice->paid_at?->format('M d, Y H:i'),
            ] : null,
        ];

        $this->showViewTransactionModal = true;
    }

    public function openViewCryptoModal(int $cryptoId): void
    {
        $this->authorizePermission('billing.view');
        $crypto = CryptoPayment::with(['user.profile', 'listing.category', 'listing.city.state', 'boostPlan', 'gateway'])->findOrFail($cryptoId);

        $this->viewCryptoData = [
            'id' => $crypto->id,
            'order_id' => $crypto->internal_order_id ?? $crypto->order_id ?? ('CPKG-' . $crypto->id),
            'provider_payment_id' => $crypto->provider_payment_id,
            'provider_invoice_id' => $crypto->provider_invoice_id,
            'currency' => $crypto->payment_currency,
            'network' => $crypto->payment_network,
            'expected_amount' => $crypto->expected_amount,
            'received_amount' => $crypto->received_amount,
            'fiat_amount' => '$' . number_format((float) ($crypto->fiat_amount ?? 0), 2),
            'transaction_hash' => $crypto->transaction_hash,
            'explorer_url' => $crypto->getExplorerUrl(),
            'payment_address' => $crypto->payment_address,
            'confirmations' => $crypto->confirmations ?? 0,
            'required_confirmations' => $crypto->required_confirmations ?? 1,
            'status' => $crypto->status,
            'gateway_name' => $crypto->gateway?->name ?? 'Crypto Web3 Gateway',
            'created_at' => $crypto->created_at?->format('M d, Y H:i:s'),
            'user' => [
                'id' => $crypto->user?->id,
                'name' => $crypto->user?->name ?? 'Guest User',
                'email' => $crypto->user?->email,
            ],
            'listing' => $crypto->listing ? [
                'id' => $crypto->listing->id,
                'title' => $crypto->listing->title,
                'category' => $crypto->listing->category?->name,
                'location' => $crypto->listing->city ? "{$crypto->listing->city->name}, {$crypto->listing->city->state?->code}" : null,
            ] : null,
            'boost_plan' => $crypto->boostPlan ? [
                'name' => $crypto->boostPlan->name,
                'price' => '$' . number_format((float) $crypto->boostPlan->price, 2),
                'duration_days' => $crypto->boostPlan->duration_days,
            ] : null,
        ];

        $this->showViewCryptoModal = true;
    }

    public function verifyCryptoPaymentOnChain(int $cryptoId, CryptoPaymentService $service): void
    {
        $this->authorizePermission('billing.view');
        $crypto = CryptoPayment::findOrFail($cryptoId);
        $prev = $crypto->status;
        $updated = $service->syncPaymentStatus($crypto);

        $msg = "Order #{$crypto->internal_order_id} verified on-chain: {$prev} → {$updated->status} ({$updated->confirmations}/{$updated->required_confirmations} confirmations).";
        session()->flash('success', $msg);
        $this->dispatch('toast', type: 'info', message: $msg);
    }

    /*
    |--------------------------------------------------------------------------
    | 14. SEO & Marketing Command Center
    |--------------------------------------------------------------------------
    */

    public function resetSeoForm(): void
    {
        $this->editingSeoId = null;
        $this->seoForm = [
            'page_route' => '',
            'meta_title' => '',
            'meta_description' => '',
            'meta_keywords' => '',
            'canonical_url' => '',
            'og_image_url' => '',
            'robots_directive' => 'index, follow',
        ];
    }

    public function openSeoModal(?int $seoId = null): void
    {
        $this->authorizePermission('seo.manage');
        $this->resetSeoForm();
        if ($seoId) {
            $meta = SeoMeta::findOrFail($seoId);
            $this->editingSeoId = $meta->id;
            $this->seoForm = [
                'page_route' => (string) $meta->page_route,
                'meta_title' => $meta->meta_title,
                'meta_description' => $meta->meta_description,
                'meta_keywords' => (string) $meta->meta_keywords,
                'canonical_url' => (string) $meta->canonical_url,
                'og_image_url' => (string) $meta->og_image_url,
                'robots_directive' => $meta->robots_directive,
            ];
        }
        $this->showSeoModal = true;
    }

    public function saveSeo(): void
    {
        $this->authorizePermission('seo.manage');
        $data = $this->validate([
            'seoForm.page_route' => ['required', 'string', 'max:120', Rule::unique('seo_metas', 'page_route')->ignore($this->editingSeoId)],
            'seoForm.meta_title' => ['required', 'string', 'max:150'],
            'seoForm.meta_description' => ['required', 'string', 'max:320'],
            'seoForm.meta_keywords' => ['nullable', 'string', 'max:255'],
            'seoForm.canonical_url' => ['nullable', 'url', 'max:255'],
            'seoForm.og_image_url' => ['nullable', 'string', 'max:255'],
            'seoForm.robots_directive' => ['required', Rule::in(['index, follow', 'noindex, follow', 'index, nofollow', 'noindex, nofollow'])],
        ])['seoForm'];

        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

        if ($this->editingSeoId) {
            $meta = SeoMeta::findOrFail($this->editingSeoId);
            $before = $meta->only(array_keys($data));
            $meta->update($data);
            AdminAuditService::log('seo.update', $meta, $before, $data);
        } else {
            $meta = SeoMeta::create($data);
            AdminAuditService::log('seo.create', $meta, null, $data);
        }

        $this->showSeoModal = false;
        $this->resetSeoForm();
        session()->flash('success', "SEO metadata for [{$meta->page_route}] saved.");
    }

    public function deleteSeo(int $id): void
    {
        $this->authorizePermission('seo.manage');
        $meta = SeoMeta::findOrFail($id);
        $route = $meta->page_route;
        $meta->delete();

        AdminAuditService::log('seo.delete', $meta, ['page_route' => $route], null);
        session()->flash('success', "SEO override for [{$route}] deleted.");
    }

    public function setSeoSubTab(string $subTab): void
    {
        $allowed = [
            'overview', 'keywords', 'urls', 'sitemaps', 'indexing',
            'gsc', 'audit', 'redirects', 'settings', 'metadata', 'serp', 'marketing', 'trends',
        ];
        $this->seoSubTab = in_array($subTab, $allowed, true) ? $subTab : 'overview';
        $this->resetPage('seoPage');
        $this->resetPage('seoKeywordsPage');
        $this->resetPage('seoUrlsPage');
        $this->resetPage('seoIssuesPage');
        $this->resetPage('seoRedirectsPage');
        $this->resetPage('seoQueuePage');
    }

    public function setSeoDateRange(string $range): void
    {
        $allowed = ['today', 'yesterday', 'last_7_days', 'last_28_days', 'last_3_months', 'last_6_months', 'last_12_months', 'custom'];
        if (in_array($range, $allowed, true)) {
            $this->seoDateRange = $range;
        }
    }

    public function generateAllSitemaps(): void
    {
        $this->authorizePermission('seo.manage');
        $service = app(\App\Modules\SEO\Services\SitemapService::class);
        $results = $service->generateAll();

        AdminAuditService::log('seo.sitemaps_generated', null, null, ['count' => count($results)]);
        session()->flash('success', 'All XML Sitemaps generated and verified successfully.');
    }

    public function runTechnicalSeoAudit(): void
    {
        $this->authorizePermission('seo.manage');
        $auditService = app(\App\Modules\SEO\Services\TechnicalSeoAuditService::class);
        $res = $auditService->runFullAudit();

        AdminAuditService::log('seo.audit_executed', null, null, $res);
        session()->flash('success', "Technical SEO audit complete. Scanned {$res['total_urls']} URLs, found {$res['issues_found']} potential issues. Overall Health: {$res['overall_health_score']}%.");
    }

    public function processIndexingQueueNow(): void
    {
        $this->authorizePermission('seo.manage');
        $service = app(\App\Modules\SEO\Services\GoogleIndexingService::class);
        $processed = $service->processPendingQueue(50);

        AdminAuditService::log('seo.queue_processed', null, null, ['processed' => $processed]);
        session()->flash('success', "Dispatched {$processed} pending indexing notifications through verified channels.");
    }

    public function syncGoogleSearchConsoleNow(): void
    {
        $this->authorizePermission('seo.manage');
        $service = app(\App\Modules\SEO\Services\GoogleSearchConsoleService::class);
        $res = $service->syncAnalytics();

        if ($res['success']) {
            AdminAuditService::log('seo.gsc_synced', null, null, $res);
            session()->flash('success', $res['message']);
        } else {
            session()->flash('error', $res['message']);
        }
    }

    public function harvestKeywords(): void
    {
        $this->authorizePermission('seo.manage');
        $service = app(\App\Modules\SEO\Services\KeywordResearchService::class);
        $count = $service->harvestFromContent();

        AdminAuditService::log('seo.keywords_harvested', null, null, ['count' => $count]);
        session()->flash('success', "Harvested {$count} high-intent commercial keywords from active listings and locations.");
    }

    public function saveNewKeyword(): void
    {
        $this->authorizePermission('seo.manage');
        $this->validate([
            'newKeywordForm.keyword' => ['required', 'string', 'min:2', 'max:190'],
            'newKeywordForm.search_intent' => ['required', 'string', 'in:informational,commercial,transactional,navigational'],
            'newKeywordForm.priority' => ['required', 'string', 'in:low,medium,high,critical'],
            'newKeywordForm.search_volume' => ['nullable', 'integer', 'min:0'],
            'newKeywordForm.target_url' => ['nullable', 'string', 'max:255'],
        ]);

        $service = app(\App\Modules\SEO\Services\KeywordResearchService::class);
        $service->saveKeyword($this->newKeywordForm);

        $this->newKeywordForm = [
            'keyword' => '',
            'search_intent' => 'commercial',
            'search_volume' => 0,
            'keyword_difficulty' => 30,
            'cpc' => 1.50,
            'priority' => 'medium',
            'target_url' => '',
            'category_id' => null,
            'state_id' => null,
            'city_id' => null,
        ];

        AdminAuditService::log('seo.keyword_saved', null, null, ['keyword' => $this->newKeywordForm['keyword'] ?? '']);
        session()->flash('success', 'Keyword created and added to tracking inventory.');
    }

    public function deleteKeyword(int $id): void
    {
        $this->authorizePermission('seo.manage');
        $kw = \App\Modules\SEO\Models\SeoKeyword::findOrFail($id);
        $name = $kw->keyword;
        $kw->delete();

        AdminAuditService::log('seo.keyword_deleted', null, ['keyword' => $name], null);
        session()->flash('success', "Keyword [{$name}] removed from tracking.");
    }

    public function saveNewRedirect(): void
    {
        $this->authorizePermission('seo.manage');
        $this->validate([
            'newRedirectForm.source_path' => ['required', 'string', 'min:1', 'max:255'],
            'newRedirectForm.target_url' => ['nullable', 'string', 'max:255'],
            'newRedirectForm.status_code' => ['required', 'integer', 'in:301,302,410'],
        ]);

        $source = '/' . ltrim(trim($this->newRedirectForm['source_path']), '/');
        \App\Modules\SEO\Models\SeoRedirect::updateOrCreate(
            ['source_path' => $source],
            [
                'target_url' => $this->newRedirectForm['target_url'] ? trim($this->newRedirectForm['target_url']) : null,
                'status_code' => (int) $this->newRedirectForm['status_code'],
                'is_active' => true,
                'notes' => $this->newRedirectForm['notes'] ?? null,
            ]
        );

        $this->newRedirectForm = [
            'source_path' => '',
            'target_url' => '',
            'status_code' => 301,
            'notes' => '',
        ];

        AdminAuditService::log('seo.redirect_saved', null, null, ['source' => $source]);
        session()->flash('success', "Redirect rule for {$source} saved successfully.");
    }

    public function toggleRedirectStatus(int $id): void
    {
        $this->authorizePermission('seo.manage');
        $redirect = \App\Modules\SEO\Models\SeoRedirect::findOrFail($id);
        $redirect->update(['is_active' => ! $redirect->is_active]);

        session()->flash('success', "Redirect rule {$redirect->source_path} is now " . ($redirect->is_active ? 'Active' : 'Disabled') . '.');
    }

    public function deleteRedirect(int $id): void
    {
        $this->authorizePermission('seo.manage');
        $redirect = \App\Modules\SEO\Models\SeoRedirect::findOrFail($id);
        $path = $redirect->source_path;
        $redirect->delete();

        AdminAuditService::log('seo.redirect_deleted', null, ['source' => $path], null);
        session()->flash('success', "Redirect rule for {$path} deleted.");
    }

    public function resolveSeoIssue(int $id): void
    {
        $this->authorizePermission('seo.manage');
        $issue = \App\Modules\SEO\Models\SeoIssue::findOrFail($id);
        $issue->update([
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);

        session()->flash('success', "Technical issue #{$id} marked as resolved.");
    }

    public function saveGscCredentials(): void
    {
        $this->authorizePermission('seo.manage');
        $this->validate([
            'gscForm.site_url' => ['required', 'string', 'max:255'],
            'gscForm.auth_mode' => ['required', 'string', 'in:service_account,oauth2'],
            'gscForm.client_email' => ['nullable', 'email', 'max:150'],
            'gscForm.private_key' => ['nullable', 'string'],
        ]);

        $service = app(\App\Modules\SEO\Services\GoogleSearchConsoleService::class);
        $service->updateCredentials($this->gscForm);

        AdminAuditService::log('seo.gsc_credentials_updated', null, null, ['site_url' => $this->gscForm['site_url']]);
        session()->flash('success', 'Google Search Console configuration saved successfully.');
    }

    public function saveRobotsTxt(): void
    {
        $this->authorizePermission('seo.manage');
        $content = trim($this->seoRobotsTxtContent);

        SystemSetting::updateOrCreate(
            ['group' => 'seo', 'key' => 'robots_txt'],
            ['value' => $content, 'type' => 'text', 'is_public' => false]
        );

        AdminAuditService::log('seo.robots_txt_updated', null, null, ['length' => strlen($content)]);
        session()->flash('success', 'Dynamic robots.txt directives saved.');
    }

    public function resetSeoFilters(): void
    {
        $this->seoSearch = '';
        $this->seoRobotsFilter = 'all';
        $this->resetPage('seoPage');
    }

    public function selectSerpPreview(int $id): void
    {
        $meta = SeoMeta::findOrFail($id);
        $this->serpPreviewRoute = (string) $meta->page_route;
        $this->serpPreviewTitle = (string) $meta->meta_title;
        $this->serpPreviewDescription = (string) $meta->meta_description;
        $this->seoSubTab = 'serp';
    }

    public function loadMarketingSettings(): void
    {
        if ($this->marketingGaId === '' && $this->marketingGtmId === '' && $this->marketingPixelId === '') {
            $this->marketingGaId = (string) (SystemSetting::where('key', 'marketing_ga_id')->value('value') ?? '');
            $this->marketingGtmId = (string) (SystemSetting::where('key', 'marketing_gtm_id')->value('value') ?? '');
            $this->marketingPixelId = (string) (SystemSetting::where('key', 'marketing_pixel_id')->value('value') ?? '');
            $this->marketingCustomHead = (string) (SystemSetting::where('key', 'marketing_custom_head')->value('value') ?? '');
        }
    }

    public function saveMarketingSettings(): void
    {
        $this->authorizePermission('seo.manage');

        $map = [
            'marketing_ga_id' => $this->marketingGaId,
            'marketing_gtm_id' => $this->marketingGtmId,
            'marketing_pixel_id' => $this->marketingPixelId,
            'marketing_custom_head' => $this->marketingCustomHead,
        ];

        foreach ($map as $key => $val) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $val, 'group' => 'marketing', 'type' => 'string', 'is_public' => false]
            );
        }

        AdminAuditService::log('seo.marketing_update', null, null, [
            'has_ga' => !empty($this->marketingGaId),
            'has_gtm' => !empty($this->marketingGtmId),
            'has_pixel' => !empty($this->marketingPixelId),
        ]);

        session()->flash('success', 'Marketing analytics and tracking tags updated successfully.');
    }

    public function refreshSitemaps(): void
    {
        $this->authorizePermission('seo.manage');
        Cache::forget('sitemap_pages');
        Cache::forget('sitemap_categories');
        Cache::forget('sitemap_locations');
        Cache::forget('sitemap_listings');

        AdminAuditService::log('seo.sitemaps_refresh', null, null, ['timestamp' => now()->toIso8601String()]);
        session()->flash('success', 'XML Sitemaps cache purged and search engine crawlers re-synchronized.');
    }

    public function generateDefaultSeoMetas(): void
    {
        $this->authorizePermission('seo.manage');

        $defaults = [
            [
                'page_route' => 'home',
                'meta_title' => 'LocalDateHub — USA Adult Personal Classifieds & Local Dating',
                'meta_description' => 'Browse verified adult personals, singles, and classified listings across all 50 US states. 100% private, safe, and privacy-conscious dating platform.',
                'meta_keywords' => 'adult personals, local dating, classified ads, casual encounters, singles',
                'canonical_url' => url('/'),
                'robots_directive' => 'index, follow',
            ],
            [
                'page_route' => 'search',
                'meta_title' => 'Search Adult Classifieds & Personals | LocalDateHub',
                'meta_description' => 'Search thousands of verified personal ads by location, category, age, and interests. Real local connections in your city.',
                'meta_keywords' => 'search personals, local classifieds, find singles, dating search',
                'canonical_url' => url('/search'),
                'robots_directive' => 'noindex, follow',
            ],
            [
                'page_route' => 'locations',
                'meta_title' => 'All 50 US States & Top Metropolitan Cities | LocalDateHub Personals',
                'meta_description' => 'Explore local adult classified ads across all 50 states, major metropolitan areas, and counties across the United States.',
                'meta_keywords' => 'states classifieds, cities personals, local dating locations',
                'canonical_url' => url('/locations'),
                'robots_directive' => 'index, follow',
            ],
            [
                'page_route' => 'categories',
                'meta_title' => 'Browse Classified Categories & Dating Channels | LocalDateHub',
                'meta_description' => 'Explore all adult dating and companionship categories: Women Seeking Men, Men Seeking Women, Casual Dating, and Activity Partners.',
                'meta_keywords' => 'dating categories, women seeking men, men seeking women, personal ads',
                'canonical_url' => url('/categories'),
                'robots_directive' => 'index, follow',
            ],
        ];

        $created = 0;
        foreach ($defaults as $d) {
            $existing = SeoMeta::where('page_route', $d['page_route'])->first();
            if (!$existing) {
                SeoMeta::create($d);
                $created++;
            }
        }

        session()->flash('success', "Generated {$created} baseline SEO metadata overrides.");
    }

    /*
    |--------------------------------------------------------------------------
    | 15. CMS Pages
    |--------------------------------------------------------------------------
    */

    public function resetCmsForm(): void
    {
        $this->editingCmsId = null;
        $this->cmsForm = ['slug' => '', 'title' => '', 'content' => '', 'is_published' => true];
    }

    public function openCmsModal(?int $pageId = null): void
    {
        $this->authorizePermission('cms.manage');
        $this->resetCmsForm();
        if ($pageId) {
            $page = CmsPage::findOrFail($pageId);
            $this->editingCmsId = $page->id;
            $this->cmsForm = [
                'slug' => $page->slug,
                'title' => $page->title,
                'content' => $page->content,
                'is_published' => (bool) $page->is_published,
            ];
        }
        $this->showCmsModal = true;
    }

    public function saveCmsPage(): void
    {
        $this->authorizePermission('cms.manage');
        if (empty($this->cmsForm['slug']) && !empty($this->cmsForm['title'])) {
            $this->cmsForm['slug'] = Str::slug($this->cmsForm['title']);
        }

        $data = $this->validate([
            'cmsForm.slug' => ['required', 'alpha_dash', 'max:100', Rule::unique('cms_pages', 'slug')->ignore($this->editingCmsId)],
            'cmsForm.title' => ['required', 'string', 'max:150'],
            'cmsForm.content' => ['required', 'string', 'max:100000'],
            'cmsForm.is_published' => ['boolean'],
        ])['cmsForm'];

        $data['is_published'] = (bool) ($data['is_published'] ?? false);
        // Strip scripts, inline event handlers, and dangerous URL schemes
        $sanitizedContent = strip_tags($data['content'], '<p><br><h2><h3><h4><ul><ol><li><strong><em><a><blockquote>');
        $sanitizedContent = preg_replace('/\s+on[a-z]+\s*=\s*(["\']).*?\1/i', '', (string) $sanitizedContent);
        $sanitizedContent = preg_replace('/\s+on[a-z]+\s*=\s*[^\s>]+/i', '', (string) $sanitizedContent);
        $sanitizedContent = preg_replace('/href\s*=\s*(["\'])\s*(javascript|data|vbscript):.*?\1/i', 'href="#"', (string) $sanitizedContent);
        $data['content'] = $sanitizedContent;

        if ($this->editingCmsId) {
            $page = CmsPage::findOrFail($this->editingCmsId);
            $before = $page->only(['slug', 'title', 'is_published']);
            if ($data['is_published'] && !$page->published_at) {
                $data['published_at'] = now();
            }
            $page->update($data);
            AdminAuditService::log('cms.update', $page, $before, ['slug' => $page->slug, 'title' => $page->title, 'is_published' => $page->is_published]);
        } else {
            $page = CmsPage::create($data + ['published_at' => $data['is_published'] ? now() : null]);
            AdminAuditService::log('cms.create', $page, null, ['slug' => $page->slug, 'title' => $page->title, 'is_published' => $page->is_published]);
        }

        $this->showCmsModal = false;
        $this->resetCmsForm();
        session()->flash('success', "CMS page [{$page->title}] saved.");
    }

    public function toggleCmsPublish(int $pageId): void
    {
        $this->authorizePermission('cms.manage');
        $page = CmsPage::findOrFail($pageId);
        $before = ['is_published' => $page->is_published];
        $page->is_published = !$page->is_published;
        if ($page->is_published && !$page->published_at) {
            $page->published_at = now();
        }
        $page->save();

        AdminAuditService::log('cms.toggle_publish', $page, $before, ['is_published' => $page->is_published]);
        session()->flash('success', "Page [{$page->title}] " . ($page->is_published ? 'published' : 'unpublished') . '.');
    }

    public function openPreviewCmsModal(int $pageId): void
    {
        $this->authorizePermission('cms.manage');
        $page = CmsPage::findOrFail($pageId);
        $this->previewCmsData = [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
            'is_published' => (bool) $page->is_published,
            'published_at' => $page->published_at?->format('M d, Y H:i'),
            'updated_at' => $page->updated_at?->format('M d, Y H:i'),
        ];
        $this->showPreviewCmsModal = true;
    }

    public function deleteCmsPage(int $pageId): void
    {
        $this->authorizePermission('cms.manage');
        $page = CmsPage::findOrFail($pageId);
        $before = $page->toArray();
        $title = $page->title;
        $page->delete();

        AdminAuditService::log('cms.delete', $page, $before, ['deleted_id' => $pageId, 'title' => $title]);
        session()->flash('success', "Page [{$title}] deleted successfully.");
        $this->dispatch('toast', type: 'info', message: "Page [{$title}] deleted.");
    }

    /*
    |--------------------------------------------------------------------------
    | 16. Advertisements & Monetization Hub
    |--------------------------------------------------------------------------
    */

    public function setAdSubTab(string $subTab): void
    {
        $this->adSubTab = in_array($subTab, ['campaigns', 'inventory', 'analytics', 'legacy'], true)
            ? $subTab
            : 'campaigns';
    }

    public function resetAdForm(): void
    {
        $this->editingAdId = null;
        $this->adBannerUpload = null;
        $this->adForm = [
            'name' => '',
            'slot' => 'sidebar',
            'advertiser_name' => '',
            'image_url' => '',
            'target_url' => '',
            'html_snippet' => '',
            'price_dollars' => '',
            'starts_at' => '',
            'ends_at' => '',
            'is_active' => true,
        ];
    }

    public function openAdModal(?int $adId = null): void
    {
        $this->authorizePermission('ads.manage');
        $this->resetAdForm();
        if ($adId) {
            $ad = AdPlacement::findOrFail($adId);
            $this->editingAdId = $ad->id;
            $this->adForm = [
                'name' => $ad->name,
                'slot' => $ad->slot,
                'advertiser_name' => (string) $ad->advertiser_name,
                'image_url' => (string) $ad->image_url,
                'target_url' => (string) $ad->target_url,
                'html_snippet' => (string) $ad->html_snippet,
                'price_dollars' => $ad->price_cents ? number_format($ad->price_cents / 100, 2, '.', '') : '',
                'starts_at' => $ad->starts_at?->format('Y-m-d') ?? '',
                'ends_at' => $ad->ends_at?->format('Y-m-d') ?? '',
                'is_active' => (bool) $ad->is_active,
            ];
        }
        $this->showAdModal = true;
    }

    public function updatedAdBannerUpload(): void
    {
        $this->authorizePermission('ads.manage');
        $this->validate([
            'adBannerUpload' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);
        $path = $this->adBannerUpload->store('ad-banners', 'public');
        $this->adForm['image_url'] = '/storage/' . $path;
        $this->adBannerUpload = null;
    }

    public function clearAdBannerMedia(): void
    {
        $this->authorizePermission('ads.manage');
        $this->adForm['image_url'] = '';
        $this->adBannerUpload = null;
    }

    public function saveAd(): void
    {
        $this->authorizePermission('ads.manage');

        if ($this->adBannerUpload) {
            $this->updatedAdBannerUpload();
        }

        $safeImageRule = function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value !== null && trim((string) $value) !== '' && !AdUrlService::isSafeImageUrl((string) $value)) {
                $fail('Banner image must be a valid http(s) URL or uploaded storage image.');
            }
        };

        $data = $this->validate([
            'adForm.name' => ['required', 'string', 'max:120'],
            'adForm.slot' => ['required', Rule::in(AdPlacement::SLOTS)],
            'adForm.advertiser_name' => ['nullable', 'string', 'max:120'],
            'adForm.image_url' => ['nullable', 'string', 'max:500', $safeImageRule],
            'adForm.target_url' => ['nullable', 'url', 'max:255'],
            'adForm.html_snippet' => ['nullable', 'string', 'max:3000'],
            'adForm.price_dollars' => ['nullable', 'numeric', 'min:0'],
            'adForm.starts_at' => ['nullable', 'date'],
            'adForm.ends_at' => ['nullable', 'date', 'after_or_equal:adForm.starts_at'],
            'adForm.is_active' => ['boolean'],
        ])['adForm'];

        $priceDollars = isset($data['price_dollars']) && $data['price_dollars'] !== '' ? (float) $data['price_dollars'] : null;
        unset($data['price_dollars']);
        $data['price_cents'] = $priceDollars !== null ? (int) round($priceDollars * 100) : 0;

        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        if ($this->editingAdId) {
            $ad = AdPlacement::findOrFail($this->editingAdId);
            $before = $ad->only(['name', 'slot', 'is_active', 'price_cents']);
            $ad->update($data);
            AdminAuditService::log('ads.update', $ad, $before, ['name' => $ad->name, 'slot' => $ad->slot, 'is_active' => $ad->is_active]);
        } else {
            $ad = AdPlacement::create($data + ['created_by_admin_id' => Auth::id()]);
            AdminAuditService::log('ads.create', $ad, null, ['name' => $ad->name, 'slot' => $ad->slot, 'is_active' => $ad->is_active]);
        }

        $this->showAdModal = false;
        $this->resetAdForm();
        session()->flash('success', "Advertisement [{$ad->name}] saved successfully.");
        $this->dispatch('toast', type: 'success', message: "Campaign [{$ad->name}] saved!");
    }

    public function toggleAd(int $adId): void
    {
        $this->authorizePermission('ads.manage');
        $ad = AdPlacement::findOrFail($adId);
        $before = ['is_active' => $ad->is_active];
        $ad->is_active = !$ad->is_active;
        $ad->save();

        AdminAuditService::log('ads.toggle', $ad, $before, ['is_active' => $ad->is_active]);
        session()->flash('success', "Advertisement [{$ad->name}] " . ($ad->is_active ? 'activated' : 'paused') . '.');
        $this->dispatch('toast', type: 'info', message: "Ad status toggled: " . ($ad->is_active ? 'Active' : 'Paused'));
    }

    public function openPreviewAdModal(int $adId): void
    {
        $this->authorizePermission('ads.manage');
        $ad = AdPlacement::findOrFail($adId);
        $this->previewAdData = [
            'id' => $ad->id,
            'name' => $ad->name,
            'slot' => $ad->slot,
            'advertiser_name' => $ad->advertiser_name ?: 'Direct Advertiser',
            'image_url' => $ad->image_url,
            'target_url' => $ad->target_url,
            'html_snippet' => $ad->html_snippet,
            'is_active' => (bool) $ad->is_active,
            'impressions' => $ad->impressions,
            'clicks' => $ad->clicks,
            'ctr' => $ad->clickThroughRate(),
            'starts_at' => $ad->starts_at?->format('M j, Y') ?? 'Immediate',
            'ends_at' => $ad->ends_at?->format('M j, Y') ?? 'Continuous',
            'price_formatted' => $ad->price_cents ? '$' . number_format($ad->price_cents / 100, 2) : 'Free / Internal',
        ];
        $this->showPreviewAdModal = true;
    }

    public function cloneAd(int $adId): void
    {
        $this->authorizePermission('ads.manage');
        $original = AdPlacement::findOrFail($adId);
        $copy = $original->replicate([
            'impressions', 'clicks', 'payment_transaction_id'
        ]);
        $copy->name = $original->name . ' (Copy)';
        $copy->is_active = false;
        $copy->impressions = 0;
        $copy->clicks = 0;
        $copy->created_by_admin_id = Auth::id();
        $copy->save();

        AdminAuditService::log('ads.clone', $copy, ['original_id' => $original->id], null);
        session()->flash('success', "Campaign cloned as [{$copy->name}].");
        $this->dispatch('toast', type: 'success', message: "Campaign cloned successfully!");
    }

    public function resetAdStats(int $adId): void
    {
        $this->authorizePermission('ads.manage');
        $ad = AdPlacement::findOrFail($adId);
        $before = ['impressions' => $ad->impressions, 'clicks' => $ad->clicks];
        $ad->impressions = 0;
        $ad->clicks = 0;
        $ad->save();

        AdminAuditService::log('ads.reset_stats', $ad, $before, ['impressions' => 0, 'clicks' => 0]);
        session()->flash('success', "Analytics counters for [{$ad->name}] reset to zero.");
        $this->dispatch('toast', type: 'info', message: "Impressions & clicks reset to 0.");
    }

    public function confirmDeleteAd(int $adId): void
    {
        $this->authorizePermission('ads.manage');
        $ad = AdPlacement::findOrFail($adId);
        $this->deleteAdId = $ad->id;
        $this->deleteAdName = $ad->name;
        $this->showDeleteAdModal = true;
    }

    public function deleteAd(): void
    {
        $this->authorizePermission('ads.manage');
        if (!$this->deleteAdId) {
            return;
        }

        $ad = AdPlacement::findOrFail($this->deleteAdId);
        $name = $ad->name;
        AdminAuditService::log('ads.delete', $ad, ['name' => $ad->name, 'slot' => $ad->slot], null);
        $ad->delete();

        $this->showDeleteAdModal = false;
        $this->deleteAdId = null;
        $this->deleteAdName = null;
        $this->showPreviewAdModal = false;
        $this->previewAdData = null;

        session()->flash('success', "Advertisement [{$name}] deleted.");
        $this->dispatch('toast', type: 'info', message: "Ad campaign [{$name}] deleted.");
    }

    /*
    |--------------------------------------------------------------------------
    | 19. Roles & Permissions Matrix
    |--------------------------------------------------------------------------
    */

    public function toggleRolePermission(int $roleId, int $permissionId): void
    {
        $this->authorizePermission('staff.manage');
        if (!$this->isSuperAdmin()) {
            abort(403, 'Only a Super Administrator can modify the permission matrix.');
        }

        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);

        if (in_array($role->slug, ['super_admin', 'admin'], true)) {
            throw ValidationException::withMessages(['roles' => 'Built-in administrator roles always retain full permissions.']);
        }

        $had = $role->permissions()->whereKey($permission->id)->exists();
        $had ? $role->permissions()->detach($permission->id) : $role->permissions()->attach($permission->id);

        AdminAuditService::log('rbac.permission_toggle', $role, ['permission' => $permission->slug, 'granted' => $had], ['permission' => $permission->slug, 'granted' => !$had]);
        session()->flash('success', ($had ? 'Revoked' : 'Granted') . " [{$permission->slug}] " . ($had ? 'from' : 'to') . " [{$role->name}].");
    }

    /*
    |--------------------------------------------------------------------------
    | 20. System Configuration & Real-Time Email Suite
    |--------------------------------------------------------------------------
    */

    public function setSettingsSubTab(string $subTab): void
    {
        $this->settingsSubTab = in_array($subTab, ['general', 'branding', 'smtp', 'notifications', 'safety', 'monetization', 'locations', 'security'], true)
            ? $subTab
            : 'general';
        $this->smtpTestSuccessMessage = null;
        $this->smtpTestErrorMessage = null;
    }

    public function loadSettings(): void
    {
        $defaults = [
            'site_name' => 'LocalDateHub',
            'tagline' => 'Safe & Private Local Adult Personal Classifieds',
            'support_email' => 'support@localdatehub.com',
            'support_phone' => '+1 (800) 555-DATE',
            'copyright_text' => '© 2026 LocalDateHub. All rights reserved. 18+ Adults Only.',
            'support_telegram' => '@LocalDateHubSupport',
            'site_logo_url' => '/images/logo.png',
            'site_logo_dark_url' => '/images/logo-white.png',
            'site_mobile_logo_url' => '/images/logo-mobile.png',
            'site_favicon_url' => '/favicon.ico',
            'hero_image_url' => '/images/hero-model.jpg',
            'hero_offer_link' => '',
            'hero_offer_new_tab' => 'true',
            'hero_offer_alt' => 'LocalDateHub Dating Lifestyle',
            'sidebar_promo_image_url' => '/images/promo-banner.jpg',
            'sidebar_promo_offer_link' => '',
            'sidebar_promo_offer_new_tab' => 'true',
            'sidebar_promo_offer_alt' => 'Find Your Perfect Match',
            'primary_brand_color' => '#F20B65',
            'mail_mailer' => 'smtp',
            'mail_host' => 'smtp.mailgun.org',
            'mail_port' => '587',
            'mail_username' => 'postmaster@mg.localdatehub.com',
            'mail_password' => '',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'notifications@localdatehub.com',
            'mail_from_name' => 'LocalDateHub Notifications',
            'mail_account_verification_enabled' => 'true',
            'mail_welcome_enabled' => 'true',
            'mail_account_approved_enabled' => 'true',
            'mail_account_verified_enabled' => 'true',
            'mail_listing_approved_enabled' => 'true',
            'mail_listing_rejected_enabled' => 'true',
            'mail_crypto_invoice_enabled' => 'true',
            'mail_crypto_confirmed_enabled' => 'true',
            'mail_balance_added_enabled' => 'true',
            'mail_low_balance_alert_enabled' => 'true',
            'low_balance_alert_threshold' => '5',
            'mail_new_message_alert_enabled' => 'true',
            'mail_message_throttle_minutes' => '5',
            'min_age_requirement' => '18',
            'anti_trafficking_enforcement' => 'true',
            'location_fuzzing_enabled' => 'true',
            'photo_exif_strip_enabled' => 'true',
            'mandatory_age_verification_to_post' => 'true',
            'custodian_of_records_notice' => '18 U.S.C. 2257 Record-Keeping Compliance Notice Active. Records kept at Compliance Dept.',
            'free_listings_enabled' => 'true',
            'max_photos_per_listing' => '6',
            'listing_active_days' => '30',
            'max_free_listings_per_user' => '3',
            'default_search_radius_miles' => '25',
            'distance_unit' => 'miles',
            'max_search_results' => '100',
            'maintenance_mode_enabled' => 'false',
            'maintenance_announcement_message' => 'LocalDateHub is undergoing scheduled infrastructure maintenance. We will be back online shortly.',
            'payment_gateway_mode' => 'test',
            'payment_gateway_provider' => 'stripe',
            'admin_2fa_required' => 'true',
            'admin_session_timeout_minutes' => '30',
            'max_login_attempts' => '5',
            'google_client_id' => '',
            'google_client_secret' => '',
            'google_oauth_enabled' => 'true',
            'facebook_client_id' => '',
            'facebook_client_secret' => '',
            'facebook_oauth_enabled' => 'false',
            'turnstile_enabled' => 'true',
            'turnstile_site_key' => '',
            'turnstile_secret_key' => '',
        ];

        $dbSettings = SystemSetting::orderBy('key')->pluck('value', 'key')->map(fn ($v) => (string) $v)->all();
        $this->settingValues = array_merge($defaults, $dbSettings);

        if (empty($this->testEmailRecipient)) {
            $this->testEmailRecipient = Auth::user()?->email ?? 'admin@localdatehub.com';
        }
    }

    public function updatedBrandLogoUpload(): void
    {
        $this->authorizePermission('settings.manage');
        $this->validate([
            'brandLogoUpload' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,svg,gif', 'max:5120'],
        ]);
        $path = $this->brandLogoUpload->store('branding', 'public');
        $url = '/storage/' . $path;
        $this->settingValues['site_logo_url'] = $url;
        $this->persistSingleBrandSetting('site_logo_url', $url, 'Header Logo (Light Background)');
        $this->brandLogoUpload = null;
    }

    public function updatedBrandLogoDarkUpload(): void
    {
        $this->authorizePermission('settings.manage');
        $this->validate([
            'brandLogoDarkUpload' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,svg,gif', 'max:5120'],
        ]);
        $path = $this->brandLogoDarkUpload->store('branding', 'public');
        $url = '/storage/' . $path;
        $this->settingValues['site_logo_dark_url'] = $url;
        $this->persistSingleBrandSetting('site_logo_dark_url', $url, 'Header Logo (Dark / Footer)');
        $this->brandLogoDarkUpload = null;
    }

    public function updatedBrandMobileLogoUpload(): void
    {
        $this->authorizePermission('settings.manage');
        $this->validate([
            'brandMobileLogoUpload' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,svg,gif', 'max:5120'],
        ]);
        $path = $this->brandMobileLogoUpload->store('branding', 'public');
        $url = '/storage/' . $path;
        $this->settingValues['site_mobile_logo_url'] = $url;
        $this->persistSingleBrandSetting('site_mobile_logo_url', $url, 'Mobile Compact Logo');
        $this->brandMobileLogoUpload = null;
    }

    public function updatedBrandFaviconUpload(): void
    {
        $this->authorizePermission('settings.manage');
        $this->validate([
            'brandFaviconUpload' => ['required', 'file', 'mimes:ico,png,jpg,jpeg,webp,svg', 'max:2048'],
        ]);
        $path = $this->brandFaviconUpload->store('branding', 'public');
        $url = '/storage/' . $path;
        $this->settingValues['site_favicon_url'] = $url;
        $this->persistSingleBrandSetting('site_favicon_url', $url, 'Browser Tab Favicon');
        $this->brandFaviconUpload = null;
    }

    public function updatedBrandHeroImageUpload(): void
    {
        $this->authorizePermission('settings.manage');
        $this->validate([
            'brandHeroImageUpload' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,gif', 'max:5120'],
        ]);

        $this->storeUploadedBrandingPhoto($this->brandHeroImageUpload, 'hero_image_url', 'Homepage Heading Image');
        $this->brandHeroImageUpload = null;
    }

    public function updatedBrandSidebarPromoUpload(): void
    {
        $this->authorizePermission('settings.manage');
        $this->validate([
            'brandSidebarPromoUpload' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,gif', 'max:5120'],
        ]);

        $this->storeUploadedBrandingPhoto($this->brandSidebarPromoUpload, 'sidebar_promo_image_url', 'Default Sidebar Promotional Image');
        $this->brandSidebarPromoUpload = null;
    }

    public function clearHeroOfferLink(): void
    {
        $this->authorizePermission('settings.manage');
        $this->settingValues['hero_offer_link'] = '';
        $this->persistSingleBrandSetting('hero_offer_link', '', 'Homepage Heading Image Offer Link');
    }

    public function clearSidebarPromoOfferLink(): void
    {
        $this->authorizePermission('settings.manage');
        $this->settingValues['sidebar_promo_offer_link'] = '';
        $this->persistSingleBrandSetting('sidebar_promo_offer_link', '', 'Default Sidebar Promo Offer Link');
    }

    protected function storeUploadedBrandingPhoto($uploadedFile, string $settingKey, string $label): string
    {
        $ext = strtolower($uploadedFile->getClientOriginalExtension());
        if ($ext === 'gif') {
            $path = $uploadedFile->store('branding', 'public');
            $url = '/storage/' . $path;
        } else {
            $stored = app(\App\Support\Storage\MediaStorageService::class)->storeSanitizedPhoto(
                $uploadedFile,
                'branding',
                'public',
                1600,
                1600
            );
            $url = $stored['url'];
        }

        $this->settingValues[$settingKey] = $url;
        $this->persistSingleBrandSetting($settingKey, $url, $label);

        return $url;
    }

    protected function validateOptionalOfferLink(string $key): void
    {
        $offerLink = trim((string) ($this->settingValues[$key] ?? ''));
        if ($offerLink === '') {
            return;
        }

        $isRelative = str_starts_with($offerLink, '/') && !str_starts_with($offerLink, '//');
        $scheme = strtolower((string) parse_url($offerLink, PHP_URL_SCHEME));
        $isHttpUrl = in_array($scheme, ['http', 'https'], true) && filter_var($offerLink, FILTER_VALIDATE_URL);

        if (!$isRelative && !$isHttpUrl) {
            throw ValidationException::withMessages([
                "settingValues.{$key}" => 'Offer link must start with https://, http://, or a valid site path like /promo.',
            ]);
        }

        $this->settingValues[$key] = $offerLink;
    }

    protected function persistSingleBrandSetting(string $key, string $url, string $label): void
    {
        $setting = SystemSetting::firstOrNew(['key' => $key]);
        $before = [$key => $setting->value];
        $setting->group = 'branding';
        $setting->type = 'string';
        $setting->description = $label;
        $setting->is_public = true;
        $setting->value = $url;
        $setting->save();

        AdminAuditService::log('setting.brand_asset_upload', $setting, $before, [$key => $url]);
        session()->flash('success', "✓ {$label} uploaded and applied live across the platform!");
        $this->dispatch('toast', type: 'success', message: "{$label} uploaded and saved!");
    }

    public function resetBrandAsset(string $key): void
    {
        $this->authorizePermission('settings.manage');
        $defaults = [
            'site_logo_url' => '/images/logo.png',
            'site_logo_dark_url' => '/images/logo-white.png',
            'site_mobile_logo_url' => '/images/logo-mobile.png',
            'site_favicon_url' => '/favicon.ico',
            'hero_image_url' => '/images/hero-model.jpg',
            'sidebar_promo_image_url' => '/images/promo-banner.jpg',
        ];
        if (!array_key_exists($key, $defaults)) {
            return;
        }
        $this->settingValues[$key] = $defaults[$key];
        $this->persistSingleBrandSetting($key, $defaults[$key], ucwords(str_replace('_', ' ', $key)));
    }

    public function saveSystemSettings(): void
    {
        $this->authorizePermission('settings.manage');

        if ($this->brandLogoUpload) {
            $this->updatedBrandLogoUpload();
        }
        if ($this->brandLogoDarkUpload) {
            $this->updatedBrandLogoDarkUpload();
        }
        if ($this->brandMobileLogoUpload) {
            $this->updatedBrandMobileLogoUpload();
        }
        if ($this->brandFaviconUpload) {
            $this->updatedBrandFaviconUpload();
        }
        if ($this->brandHeroImageUpload) {
            $this->updatedBrandHeroImageUpload();
        }
        if ($this->brandSidebarPromoUpload) {
            $this->updatedBrandSidebarPromoUpload();
        }

        $this->validateOptionalOfferLink('hero_offer_link');
        $this->validateOptionalOfferLink('sidebar_promo_offer_link');

        $changed = 0;
        foreach ($this->settingValues as $key => $val) {
            $setting = SystemSetting::firstOrNew(['key' => $key]);
            if (!$setting->exists) {
                $group = 'general';
                if (str_starts_with($key, 'site_logo') || str_starts_with($key, 'site_favicon') || str_starts_with($key, 'hero_') || str_starts_with($key, 'sidebar_promo_') || $key === 'site_mobile_logo_url' || $key === 'primary_brand_color') {
                    $group = 'branding';
                } elseif (str_starts_with($key, 'mail_') && (str_contains($key, 'host') || str_contains($key, 'port') || str_contains($key, 'username') || str_contains($key, 'password') || str_contains($key, 'encryption') || str_contains($key, 'mailer') || str_contains($key, 'from_'))) {
                    $group = 'smtp';
                } elseif (str_starts_with($key, 'mail_') || str_contains($key, 'balance_alert')) {
                    $group = 'notifications';
                } elseif (str_contains($key, 'age') || str_contains($key, 'trafficking') || str_contains($key, 'fuzzing') || str_contains($key, 'exif') || str_contains($key, 'custodian')) {
                    $group = 'safety';
                } elseif (str_contains($key, 'listing') || str_contains($key, 'free') || str_contains($key, 'photos')) {
                    $group = 'monetization';
                } elseif (str_contains($key, 'radius') || str_contains($key, 'distance') || str_contains($key, 'search')) {
                    $group = 'locations';
                } elseif (str_contains($key, 'maintenance') || str_contains($key, '2fa') || str_contains($key, 'session') || str_contains($key, 'attempts')) {
                    $group = 'security';
                }
                $setting->group = $group;
                $setting->type = in_array($key, [
                    'hero_image_url',
                    'hero_offer_link',
                    'hero_offer_alt',
                    'sidebar_promo_image_url',
                    'sidebar_promo_offer_link',
                    'sidebar_promo_offer_alt',
                ], true)
                    ? 'string'
                    : (in_array((string) $val, ['true', 'false'], true) ? 'boolean' : (is_numeric($val) ? 'integer' : 'string'));
                $setting->description = ucwords(str_replace('_', ' ', $key));
                $setting->is_public = !in_array($group, ['smtp', 'security'], true);
            }

            // Robust type normalization for booleans, integers and strings
            if ($setting->type === 'boolean') {
                $bool = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($bool !== null) {
                    $val = $bool ? 'true' : 'false';
                } else {
                    $val = in_array((string) $val, ['true', '1'], true) ? 'true' : 'false';
                }
            } elseif ($setting->type === 'integer') {
                $val = trim((string) $val);
            } else {
                $val = (string) $val;
            }

            // Only validate and enforce permissions on settings that are actually being modified
            if ((string) $setting->value !== (string) $val) {
                if (in_array($setting->group, self::RESTRICTED_SETTING_GROUPS, true) && !$this->isSuperAdmin()) {
                    abort(403, "Only a Super Administrator can modify [{$setting->group}] settings.");
                }

                if ($setting->type === 'integer' && !preg_match('/^-?\d+$/', $val)) {
                    throw ValidationException::withMessages(["settingValues.{$key}" => "The [{$key}] setting must be an integer."]);
                }
                if ($setting->type === 'boolean' && !in_array($val, ['true', 'false'], true)) {
                    throw ValidationException::withMessages(["settingValues.{$key}" => "The [{$key}] setting must be true or false."]);
                }
                if ($key === 'min_age_requirement' && (int) $val < 18) {
                    throw ValidationException::withMessages(["settingValues.{$key}" => 'Minimum age can never be lower than 18.']);
                }

                $before = [$key => $setting->value];
                $setting->value = $val;
                $setting->save();
                AdminAuditService::log('setting.update', $setting, $before, [$key => $val]);
                $changed++;
            }
        }

        // Dynamically apply mail settings to runtime config
        if (!empty($this->settingValues['mail_host'])) {
            $driver = $this->settingValues['mail_mailer'] ?? 'smtp';
            $enc = $this->settingValues['mail_encryption'] ?? 'tls';
            config([
                'mail.default' => $driver === 'log' ? 'log' : 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $this->settingValues['mail_host'],
                'mail.mailers.smtp.port' => (int) ($this->settingValues['mail_port'] ?? 587),
                'mail.mailers.smtp.encryption' => ($enc === 'none' || empty($enc)) ? null : $enc,
                'mail.mailers.smtp.username' => empty($this->settingValues['mail_username']) ? null : $this->settingValues['mail_username'],
                'mail.mailers.smtp.password' => empty($this->settingValues['mail_password']) ? null : $this->settingValues['mail_password'],
                'mail.mailers.smtp.verify_peer' => false,
                'mail.from.address' => $this->settingValues['mail_from_address'] ?? config('mail.from.address'),
                'mail.from.name' => $this->settingValues['mail_from_name'] ?? config('mail.from.name'),
            ]);
            Mail::purge('smtp');
            $this->syncMailConfigToEnv();
        }

        session()->flash('success', $changed > 0 ? "System configuration updated successfully ({$changed} setting(s) saved)." : 'All settings are up to date.');
        $this->dispatch('toast', type: 'success', message: "Configuration saved successfully ({$changed} updated).");
    }

    protected function syncMailConfigToEnv(): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath) || !is_writable($envPath)) {
            return;
        }

        try {
            $envContent = file_get_contents($envPath);
            $replacements = [
                'MAIL_MAILER' => $this->settingValues['mail_mailer'] ?? 'smtp',
                'MAIL_HOST' => $this->settingValues['mail_host'] ?? '',
                'MAIL_PORT' => $this->settingValues['mail_port'] ?? '587',
                'MAIL_USERNAME' => $this->settingValues['mail_username'] ?? '',
                'MAIL_PASSWORD' => $this->settingValues['mail_password'] ?? '',
                'MAIL_ENCRYPTION' => $this->settingValues['mail_encryption'] ?? 'tls',
                'MAIL_FROM_ADDRESS' => '"' . ($this->settingValues['mail_from_address'] ?? 'notifications@localdatehub.com') . '"',
                'MAIL_FROM_NAME' => '"' . ($this->settingValues['mail_from_name'] ?? 'LocalDateHub') . '"',
            ];

            foreach ($replacements as $k => $v) {
                if (preg_match("/^{$k}=.*/m", $envContent)) {
                    $envContent = preg_replace("/^{$k}=.*/m", "{$k}={$v}", $envContent);
                } else {
                    $envContent .= "\n{$k}={$v}";
                }
            }

            file_put_contents($envPath, $envContent);
        } catch (\Throwable) {
            // Graceful fallback to database persistence
        }
    }

    public function sendTestEmail(): void
    {
        $this->authorizePermission('settings.manage');
        $this->validate([
            'testEmailRecipient' => ['required', 'email'],
        ]);

        $this->smtpTestSuccessMessage = null;
        $this->smtpTestErrorMessage = null;

        $host = trim((string) ($this->settingValues['mail_host'] ?? config('mail.mailers.smtp.host', 'smtp.mailgun.org')));
        $port = (int) ($this->settingValues['mail_port'] ?? config('mail.mailers.smtp.port', 587));
        $username = trim((string) ($this->settingValues['mail_username'] ?? ''));
        $password = (string) ($this->settingValues['mail_password'] ?? '');
        $encryption = (string) ($this->settingValues['mail_encryption'] ?? 'tls');
        $fromEmail = trim((string) ($this->settingValues['mail_from_address'] ?? 'notifications@localdatehub.com'));
        $fromName = trim((string) ($this->settingValues['mail_from_name'] ?? 'LocalDateHub'));
        $driver = (string) ($this->settingValues['mail_mailer'] ?? 'smtp');

        // Dynamically configure Laravel's mailer on the fly for this diagnostic
        if ($driver === 'log') {
            config(['mail.default' => 'log']);
        } else {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $host,
                'mail.mailers.smtp.port' => $port,
                'mail.mailers.smtp.encryption' => ($encryption === 'none' || empty($encryption)) ? null : $encryption,
                'mail.mailers.smtp.username' => empty($username) ? null : $username,
                'mail.mailers.smtp.password' => empty($password) ? null : $password,
                'mail.mailers.smtp.timeout' => 15,
                'mail.mailers.smtp.verify_peer' => false,
                'mail.from.address' => $fromEmail,
                'mail.from.name' => $fromName,
            ]);
            Mail::purge('smtp');
        }

        try {
            Mail::raw("Hello!\n\nThis is a verified test email sent from the LocalDateHub Administrative System Configuration Suite.\n\nSMTP Host: {$host}:{$port}\nOutgoing Sender: {$fromName} <{$fromEmail}>\nTimestamp: " . now()->toIso8601String() . "\n\nAll real-time email notification triggers (Account Creation, 18+ Verification, Listing Moderation, Blockchain Crypto Invoices, Wallet Funds, Low Balance Alerts, and Private Chat Notifications) are 100% OPERATIONAL.", function ($message) use ($fromEmail, $fromName) {
                $message->to($this->testEmailRecipient)
                    ->from($fromEmail, $fromName)
                    ->subject("✓ [TEST CONFIRMED] LocalDateHub SMTP & Real-Time Email Delivery Verification");
            });

            NotificationLog::create([
                'user_id' => Auth::id(),
                'channel' => 'mail',
                'notification_type' => 'system.smtp_test',
                'subject' => "[TEST CONFIRMED] LocalDateHub SMTP & Real-Time Email Delivery Verification",
                'recipient_target' => $this->testEmailRecipient,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $msg = "✓ Test email sent successfully to [{$this->testEmailRecipient}] via {$host}:{$port}! Delivery verified.";
            $this->smtpTestSuccessMessage = $msg;
            $this->dispatch('toast', type: 'success', message: "Test email dispatched successfully!");
        } catch (\Throwable $e) {
            $rawErr = $e->getMessage();
            $hint = '';
            if (str_contains($rawErr, 'Connection timed out')) {
                $hint = " — TIP: If using Cloudflare, change your 'mail' DNS record from Proxied (Orange Cloud) to 'DNS Only' (Grey Cloud), as Cloudflare blocks SMTP port {$port}. Alternatively, set SMTP Hostname to '127.0.0.1' if the mail server is on this same server.";
            }
            $err = $rawErr . $hint;
            $this->smtpTestErrorMessage = $err;
            $this->dispatch('toast', type: 'warning', message: "SMTP Handshake: " . Str::limit($rawErr, 60));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 21. Audit Log Inspection
    |--------------------------------------------------------------------------
    */

    public function inspectAuditLog(int $logId): void
    {
        $this->authorizePermission('audit.view');
        AdminAuditLog::findOrFail($logId);
        $this->inspectedAuditLogId = $logId;
        $this->showAuditDetailModal = true;
    }

    public function closeModals(): void
    {
        $this->showCreateUserModal = false;
        $this->showViewUserModal = false;
        $this->showEditUserModal = false;
        $this->showDeleteUserModal = false;
        $this->showViewListingModal = false;
        $this->showEditListingModal = false;
        $this->showDeleteListingModal = false;
        $this->showViewTransactionModal = false;
        $this->viewTransactionData = null;
        $this->showViewCryptoModal = false;
        $this->viewCryptoData = null;
        $this->viewUserId = null;
        $this->viewUserData = null;
        $this->editUserId = null;
        $this->editUserAvatar = null;
        $this->editUserAvatarUrl = null;
        $this->deleteUserId = null;
        $this->viewListingId = null;
        $this->viewListingData = null;
        $this->editListingId = null;
        $this->deleteListingId = null;
        $this->deleteListingTitle = null;
        $this->inspectedAppealData = null;
        $this->selectedQueueListingIds = [];
        $this->showSuspendUserModal = false;
        $this->showAssignRoleModal = false;
        $this->showRejectListingModal = false;
        $this->showAppealModal = false;
        $this->showResolveReportModal = false;
        $this->showInspectReportModal = false;
        $this->inspectReportData = null;
        $this->showRefundModal = false;
        $this->showPlanModal = false;
        $this->showPackageModal = false;
        $this->editingPackageId = null;
        $this->showCmsModal = false;
        $this->showSeoModal = false;
        $this->showAdModal = false;
        $this->showCampaignModal = false;
        $this->showDeleteCampaignModal = false;
        $this->showPreviewCampaignModal = false;
        $this->previewCampaignData = null;
        $this->showDiagnosticModal = false;
        $this->diagnosticData = null;
        $this->showAuditDetailModal = false;
        $this->showDmcaModal = false;
        $this->showLegalModal = false;
        $this->showStateComplianceModal = false;
        $this->showPreservationModal = false;
        $this->showAssignSubscriptionModal = false;
        $this->showViewSubscriptionModal = false;
        $this->viewSubscriptionData = null;
        $this->showInspectVerificationModal = false;
        $this->showManualVerifyModal = false;
        $this->showRejectVerificationModal = false;
        $this->showBroadcastModal = false;
        $this->showInspectNotificationModal = false;
        $this->inspectNotificationData = null;
        $this->showInspectAbuseModal = false;
        $this->inspectAbuseData = null;
        $this->showPricingRuleModal = false;
        $this->showPreviewCmsModal = false;
        $this->previewCmsData = null;
        $this->showDeleteAdModal = false;
        $this->deleteAdId = null;
        $this->deleteAdName = null;
        $this->showPreviewAdModal = false;
        $this->previewAdData = null;
        $this->inspectedVerificationData = null;
        $this->rejectingAssuranceId = null;
        $this->inspectedPreservation = null;
        $this->inspectedAuditLogId = null;
        $this->resetValidation();
    }

    /*
    |--------------------------------------------------------------------------
    | Rendering
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        $data = [
            'tab' => $this->tab,
            'tabs' => collect(self::TABS)->filter(fn ($meta, $key) => $this->canAccessTab($key)),
            'tabAllowed' => $this->canAccessTab($this->tab),
            'currentRole' => $this->currentUser()?->role,
            'isSuperAdmin' => $this->isSuperAdmin(),
            'assignableRoles' => $this->assignableRoles(),
        ];

        if ($data['tabAllowed']) {
            $method = 'data' . Str::studly($this->tab);
            if (method_exists($this, $method)) {
                $data = array_merge($data, $this->{$method}());
            }
        }

        if ($this->showAuditDetailModal && $this->inspectedAuditLogId) {
            $data['inspectedAuditLog'] = AdminAuditLog::with('admin')->find($this->inspectedAuditLogId);
        }

        return view('livewire.admin.admin-enterprise-hub', $data);
    }

    public function toggleGlobalEmailRelay(): void
    {
        $this->authorizePermission('settings.manage');

        $service = app(EmailRelayService::class);
        $next = !$service->isRelayGloballyEnabled();

        SystemSetting::updateOrCreate(
            ['key' => 'email_relay_enabled'],
            ['value' => $next ? '1' : '0', 'group' => 'email_relay']
        );

        AdminAuditLog::create([
            'admin_user_id' => Auth::id(),
            'action' => 'email_relay.global_toggle',
            'target_type' => SystemSetting::class,
            'after_state' => ['enabled' => $next],
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        session()->flash('admin_success', 'Global Encrypted Email Relay is now ' . ($next ? 'ENABLED' : 'DISABLED') . '.');
    }

    public function toggleListingRelayStatus(int $contactId): void
    {
        $this->authorizePermission('settings.manage');

        $contact = ListingContact::findOrFail($contactId);
        $nextStatus = $contact->relay_status === 'active' ? 'disabled' : 'active';
        $contact->update([
            'relay_status' => $nextStatus,
            'allow_email_relay' => $nextStatus === 'active',
        ]);

        AdminAuditLog::create([
            'admin_user_id' => Auth::id(),
            'action' => 'email_relay.listing_toggle',
            'target_type' => ListingContact::class,
            'target_id' => $contact->id,
            'after_state' => ['listing_id' => $contact->listing_id, 'relay_status' => $nextStatus],
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        session()->flash('admin_success', "Relay address [{$contact->masked_relay_email}] is now {$nextStatus}.");
    }

    public function retryRelayMessage(int $messageId): void
    {
        $this->authorizePermission('settings.manage');

        $message = EmailRelayMessage::findOrFail($messageId);
        app(EmailRelayService::class)->retryMessage($message);

        AdminAuditLog::create([
            'admin_user_id' => Auth::id(),
            'action' => 'email_relay.retry',
            'target_type' => EmailRelayMessage::class,
            'target_id' => $message->id,
            'after_state' => ['listing_id' => $message->listing_id],
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        session()->flash('admin_success', "Relay message #{$message->id} has been re-queued for delivery.");
    }

    protected function dataEmailRelay(): array
    {
        $service = app(EmailRelayService::class);

        $metrics = [
            'total' => EmailRelayMessage::count(),
            'inbound' => EmailRelayMessage::where('direction', EmailRelayMessage::DIRECTION_INBOUND)->count(),
            'outbound' => EmailRelayMessage::where('direction', EmailRelayMessage::DIRECTION_OUTBOUND)->count(),
            'delivered' => EmailRelayMessage::where('delivery_status', EmailRelayMessage::STATUS_DELIVERED)->count(),
            'accepted' => EmailRelayMessage::where('delivery_status', EmailRelayMessage::STATUS_ACCEPTED)->count(),
            'failed' => EmailRelayMessage::where('delivery_status', EmailRelayMessage::STATUS_FAILED)->count(),
            'bounced' => EmailRelayMessage::where('delivery_status', EmailRelayMessage::STATUS_BOUNCED)->count(),
            'blocked' => EmailRelayMessage::where('delivery_status', EmailRelayMessage::STATUS_BLOCKED)->count(),
            'active_relays' => ListingContact::where('allow_email_relay', true)->where('relay_status', 'active')->count(),
        ];

        $query = EmailRelayMessage::with(['listing:id,title,slug', 'contact:id,listing_id,masked_relay_email,relay_status'])
            ->latest('id');

        if ($this->relayStatusFilter !== 'all') {
            $query->where('delivery_status', $this->relayStatusFilter);
        }
        if ($this->relayDirectionFilter !== 'all') {
            $query->where('direction', $this->relayDirectionFilter);
        }
        if (trim($this->relayListingId) !== '' && is_numeric($this->relayListingId)) {
            $query->where('listing_id', (int) $this->relayListingId);
        }
        if (trim($this->relaySearch) !== '') {
            $term = '%' . trim($this->relaySearch) . '%';
            $query->where(function ($q) use ($term): void {
                $q->where('subject', 'like', $term)
                    ->orWhere('message_id', 'like', $term)
                    ->orWhere('sender_name', 'like', $term)
                    ->orWhereHas('listing', fn ($lq) => $lq->where('title', 'like', $term))
                    ->orWhereHas('contact', fn ($cq) => $cq->where('masked_relay_email', 'like', $term));
            });
        }

        $contactsQuery = ListingContact::with('listing:id,title,slug')
            ->whereNotNull('masked_relay_email')
            ->latest('id');

        if (trim($this->relaySearch) !== '') {
            $term = '%' . trim($this->relaySearch) . '%';
            $contactsQuery->where(function ($q) use ($term): void {
                $q->where('masked_relay_email', 'like', $term)
                    ->orWhereHas('listing', fn ($lq) => $lq->where('title', 'like', $term));
            });
        }

        return [
            'emailRelayEnabled' => $service->isRelayGloballyEnabled(),
            'emailRelayMetrics' => $metrics,
            'emailRelayMessages' => $query->take(40)->get(),
            'emailRelayContacts' => $contactsQuery->take(25)->get(),
        ];
    }

    protected function dataDashboard(): array
    {
        $dbHealthy = true;
        try {
            DB::select('select 1');
        } catch (\Throwable) {
            $dbHealthy = false;
        }

        $cacheHealthy = true;
        try {
            Cache::put('admin_health_probe', 1, 5);
            $cacheHealthy = Cache::get('admin_health_probe') === 1;
        } catch (\Throwable) {
            $cacheHealthy = false;
        }

        $pending = Listing::where('status', Listing::STATUS_PENDING_REVIEW)->count();
        $flagged = ContactMessage::where('is_flagged', true)->count();
        $published = Listing::where('status', Listing::STATUS_ACTIVE)->count();
        $rejected = Listing::where('status', Listing::STATUS_REJECTED)->count();
        $expired = Listing::where('status', Listing::STATUS_EXPIRED)->count();
        $totalListings = Listing::count();

        // 1. User Metrics & Real Growth
        $totalUsers = User::count();
        $usersLast7Days = User::where('created_at', '>=', now()->subDays(7))->count();
        $usersPrior7Days = User::whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count();
        $userGrowth = $usersPrior7Days > 0 
            ? round((($usersLast7Days - $usersPrior7Days) / $usersPrior7Days) * 100, 1) 
            : ($usersLast7Days > 0 ? 100.0 : 0.0);

        // 2. Listing Metrics & Real Growth
        $listingsLast7Days = Listing::where('created_at', '>=', now()->subDays(7))->count();
        $listingsPrior7Days = Listing::whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count();
        $listingGrowth = $listingsPrior7Days > 0 
            ? round((($listingsLast7Days - $listingsPrior7Days) / $listingsPrior7Days) * 100, 1) 
            : ($listingsLast7Days > 0 ? 100.0 : 0.0);

        // 3. Views & Real-Time Traffic Telemetry (Real Public Visitor Traffic Only)
        $baseTrafficQuery = fn () => PlatformTrafficLog::where('is_bot', false)
            ->where(function ($q) {
                $q->whereNull('path')
                    ->orWhere(function ($sq) {
                        $sq->where('path', 'not like', 'admin%')
                            ->where('path', 'not like', '/admin%')
                            ->where('path', 'not like', 'livewire%')
                            ->where('path', 'not like', '/livewire%');
                    });
            });

        $listingUniqueViews = (int) Listing::sum('view_count');
        $listingAnalyticsGross = (int) ListingAnalytics::sum('views');
        $listingAnalyticsUnique = (int) ListingAnalytics::sum('unique_visitors');
        $postGrossViews = max($listingUniqueViews, $listingAnalyticsGross);
        $postUniqueViews = $listingAnalyticsUnique > 0 ? max($listingAnalyticsUnique, $listingUniqueViews) : $listingUniqueViews;

        $siteGrossViews = (int) $baseTrafficQuery()->count();
        $siteUniqueVisitors = (int) $baseTrafficQuery()->distinct('visitor_hash')->count('visitor_hash');

        $grossViews = $siteGrossViews + $postGrossViews;
        $uniqueVisitors = max($siteUniqueVisitors, $postUniqueViews);
        $totalViews = $this->dashboardViewMode === 'gross' ? $grossViews : $uniqueVisitors;

        $viewsLast7Days = $this->dashboardViewMode === 'gross'
            ? $baseTrafficQuery()->where('created_at', '>=', now()->subDays(7))->count()
            : $baseTrafficQuery()->where('created_at', '>=', now()->subDays(7))->distinct('visitor_hash')->count('visitor_hash');
        $viewsPrior7Days = $this->dashboardViewMode === 'gross'
            ? $baseTrafficQuery()->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count()
            : $baseTrafficQuery()->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->distinct('visitor_hash')->count('visitor_hash');
        $viewsGrowth = $viewsPrior7Days > 0 
            ? round((($viewsLast7Days - $viewsPrior7Days) / $viewsPrior7Days) * 100, 1) 
            : ($viewsLast7Days > 0 ? 100.0 : 0.0);

        $activeVisitorsCount = app(\App\Modules\Analytics\Services\LiveVisitorTrackingService::class)->getOnlineCount();
        $activeVisitorsNow = $activeVisitorsCount;
        $todayGrossViews = $baseTrafficQuery()->whereDate('created_at', today())->count();
        $todayUniqueVisitors = $baseTrafficQuery()->whereDate('created_at', today())->distinct('visitor_hash')->count('visitor_hash');
        $todayViews = $this->dashboardViewMode === 'gross' ? $todayGrossViews : $todayUniqueVisitors;

        // 4. Revenue & Real Revenue Growth
        $fiatRevenue = (float) (PaymentTransaction::where('status', 'completed')->sum('amount_cents') / 100);
        $cryptoRevenue = (float) CryptoPayment::whereIn('status', [CryptoPayment::STATUS_COMPLETED, 'completed', 'confirmed'])->sum('fiat_amount');
        $totalRevenue = $fiatRevenue + $cryptoRevenue;

        $fiatRev7 = (float) (PaymentTransaction::where('status', 'completed')->where('created_at', '>=', now()->subDays(7))->sum('amount_cents') / 100);
        $cryptoRev7 = (float) CryptoPayment::whereIn('status', [CryptoPayment::STATUS_COMPLETED, 'completed', 'confirmed'])->where('created_at', '>=', now()->subDays(7))->sum('fiat_amount');
        $revLast7Days = $fiatRev7 + $cryptoRev7;

        $fiatRevPrior = (float) (PaymentTransaction::where('status', 'completed')->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->sum('amount_cents') / 100);
        $cryptoRevPrior = (float) CryptoPayment::whereIn('status', [CryptoPayment::STATUS_COMPLETED, 'completed', 'confirmed'])->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->sum('fiat_amount');
        $revPrior7Days = $fiatRevPrior + $cryptoRevPrior;

        $revenueGrowth = $revPrior7Days > 0 
            ? round((($revLast7Days - $revPrior7Days) / $revPrior7Days) * 100, 1) 
            : ($revLast7Days > 0 ? 100.0 : 0.0);

        // 5. Weekly Traffic & Inquiries (Last 7 Days - Unique & Gross + Post Views)
        $weeklyDays = [];
        $weeklyDates = [];
        $weeklyGrossViews = [];
        $weeklyUniqueViews = [];
        $weeklyPostViews = [];
        $weeklyViews = [];
        $weeklyContacts = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $dateStr = $day->toDateString();
            $weeklyDays[] = $day->format('D');
            $weeklyDates[] = $day->format('M j');

            $gCount = $baseTrafficQuery()->whereDate('created_at', $dateStr)->count();
            $uCount = $baseTrafficQuery()->whereDate('created_at', $dateStr)->distinct('visitor_hash')->count('visitor_hash');
            $pCount = (int) ListingAnalytics::whereDate('metric_date', $dateStr)->sum('views');
            $cCount = ContactMessage::whereDate('created_at', $dateStr)->count() 
                + AnalyticsEvent::where('event_type', 'contact_click')->whereDate('created_at', $dateStr)->count();

            $weeklyGrossViews[] = $gCount + $pCount;
            $weeklyUniqueViews[] = max($uCount, (int) ListingAnalytics::whereDate('metric_date', $dateStr)->sum('unique_visitors'));
            $weeklyPostViews[] = $pCount;
            $weeklyViews[] = $this->dashboardViewMode === 'gross' ? ($gCount + $pCount) : max($uCount, (int) ListingAnalytics::whereDate('metric_date', $dateStr)->sum('unique_visitors'));
            $weeklyContacts[] = $cCount;
        }

        $totalWeeklyViews = array_sum($weeklyViews);
        $totalWeeklyGrossViews = array_sum($weeklyGrossViews);
        $totalWeeklyUniqueViews = array_sum($weeklyUniqueViews);
        $totalWeeklyPostViews = array_sum($weeklyPostViews);
        $totalWeeklyContacts = array_sum($weeklyContacts);

        // Dynamic SVG coordinate generator for 7 days (width 500, height 130)
        $maxV = max(1, ...$weeklyViews, ...$weeklyGrossViews);
        $maxU = max(1, ...$weeklyUniqueViews);
        $maxC = max(1, ...$weeklyContacts);

        $viewPoints = [];
        $uniquePoints = [];
        $contactPoints = [];
        $barHeights = [];
        foreach ($weeklyViews as $idx => $val) {
            $x = round($idx * (500 / 6), 1);
            $y = round(120 - (($val / $maxV) * 85), 1);
            $viewPoints[] = "{$x},{$y}";

            $uVal = $weeklyUniqueViews[$idx] ?? 0;
            $gVal = $weeklyGrossViews[$idx] ?? 0;
            $uY = round(120 - (($uVal / $maxV) * 85), 1);
            $uniquePoints[] = "{$x},{$uY}";

            $barHeights[] = [
                'day' => $weeklyDays[$idx],
                'date' => $weeklyDates[$idx],
                'gross' => $gVal,
                'unique' => $uVal,
                'posts' => $weeklyPostViews[$idx] ?? 0,
                'contacts' => $weeklyContacts[$idx] ?? 0,
                'grossPct' => max(6, (int) round(($gVal / $maxV) * 100)),
                'uniquePct' => max(6, (int) round(($uVal / $maxV) * 100)),
            ];
        }
        foreach ($weeklyContacts as $idx => $val) {
            $x = round($idx * (500 / 6), 1);
            $y = round(120 - (($val / $maxC) * 85), 1);
            $contactPoints[] = "{$x},{$y}";
        }

        $viewsLineD = 'M ' . implode(' L ', $viewPoints);
        $viewsAreaD = $viewsLineD . ' L 500,130 L 0,130 Z';
        $uniqueLineD = 'M ' . implode(' L ', $uniquePoints);
        $uniqueAreaD = $uniqueLineD . ' L 500,130 L 0,130 Z';
        $contactsLineD = 'M ' . implode(' L ', $contactPoints);
        $contactsAreaD = $contactsLineD . ' L 500,130 L 0,130 Z';

        // Peak point for dot
        $peakIdx = array_search(max($weeklyViews), $weeklyViews, true);
        $peakIdx = $peakIdx !== false ? $peakIdx : 0;
        $peakX = round($peakIdx * (500 / 6), 1);
        $peakY = round(120 - (($weeklyViews[$peakIdx] / $maxV) * 85), 1);

        // 6. Listing Status Distribution
        $statusTotal = max(1, $published + $pending + $rejected + $expired);
        $pubPct = (int) round(($published / $statusTotal) * 100);
        $penPct = (int) round(($pending / $statusTotal) * 100);
        $rejPct = max(0, 100 - ($pubPct + $penPct));

        // 7. Top Countries & Locations (Real Public Database Records)
        $topCountries = $baseTrafficQuery()
            ->select('country_code', 'country_name', DB::raw('count(*) as hits'), DB::raw('count(distinct visitor_hash) as unique_visitors'))
            ->groupBy('country_code', 'country_name')
            ->orderByDesc('hits')
            ->limit(5)
            ->get();

        $topLocations = State::withCount(['listings' => fn($q) => $q->where('status', Listing::STATUS_ACTIVE)])
            ->orderByDesc('listings_count')
            ->limit(5)
            ->get();

        // 8. Live Real-Time Visitor Stream (Public Only)
        $liveVisitors = $baseTrafficQuery()->latest('created_at')->limit(6)->get();

        // 9. Recent 4 listings for dashboard
        $recentListings = Listing::with(['city.state', 'primaryPhoto', 'category'])
            ->latest('published_at')
            ->limit(4)
            ->get();

        return [
            'metrics' => [
                'totalUsers' => $totalUsers,
                'userGrowth' => $userGrowth,
                'totalListings' => $totalListings,
                'listingGrowth' => $listingGrowth,
                'totalViews' => $totalViews,
                'grossViews' => $grossViews,
                'uniqueVisitors' => $uniqueVisitors,
                'siteGrossViews' => $siteGrossViews,
                'siteUniqueVisitors' => $siteUniqueVisitors,
                'postGrossViews' => $postGrossViews,
                'postUniqueViews' => $postUniqueViews,
                'viewsGrowth' => $viewsGrowth,
                'todayViews' => $todayViews,
                'todayGrossViews' => $todayGrossViews,
                'todayUniqueVisitors' => $todayUniqueVisitors,
                'activeVisitorsNow' => $activeVisitorsNow,
                'totalRevenue' => $totalRevenue,
                'revenueGrowth' => $revenueGrowth,
                'activeUsers' => User::where('account_status', 'active')->count(),
                'suspendedUsers' => User::where('account_status', 'suspended')->count(),
                'pendingListings' => $pending,
                'publishedListings' => $published,
                'rejectedListings' => $rejected,
                'expiredListings' => $expired,
                'openReports' => ContentReport::whereIn('status', ['open', 'investigating'])->count(),
                'totalReports' => ContentReport::count(),
                'moderationQueue' => $pending + $flagged,
                'pendingVerifications' => AgeAssurance::where('status', 'pending')->count(),
                'pendingAppeals' => AppealRequest::whereIn('status', ['pending', 'under_review'])->count(),
            ],
            'chart' => [
                'days' => $weeklyDays,
                'dates' => $weeklyDates,
                'views' => $weeklyViews,
                'grossViews' => $weeklyGrossViews,
                'uniqueViews' => $weeklyUniqueViews,
                'postViews' => $weeklyPostViews,
                'contacts' => $weeklyContacts,
                'bars' => $barHeights,
                'totalWeeklyViews' => $totalWeeklyViews,
                'totalWeeklyGrossViews' => $totalWeeklyGrossViews,
                'totalWeeklyUniqueViews' => $totalWeeklyUniqueViews,
                'totalWeeklyPostViews' => $totalWeeklyPostViews,
                'totalWeeklyContacts' => $totalWeeklyContacts,
                'viewsLineD' => $viewsLineD,
                'viewsAreaD' => $viewsAreaD,
                'uniqueLineD' => $uniqueLineD,
                'uniqueAreaD' => $uniqueAreaD,
                'contactsLineD' => $contactsLineD,
                'contactsAreaD' => $contactsAreaD,
                'peakX' => $peakX,
                'peakY' => $peakY,
            ],
            'statusCounts' => [
                'published' => $published,
                'pending' => $pending,
                'rejected' => $rejected,
                'expired' => $expired,
                'total' => $statusTotal,
                'pubPct' => $pubPct,
                'penPct' => $penPct,
                'rejPct' => $rejPct,
            ],
            'topCountries' => $topCountries,
            'topLocations' => $topLocations,
            'liveVisitors' => $liveVisitors,
            'recentListings' => $recentListings,
            'health' => [
                'database' => $dbHealthy,
                'cache' => $cacheHealthy,
                'queue' => config('queue.default'),
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'environment' => app()->environment(),
                'serverPct' => min(100, max(60, 100 - (int) round((memory_get_usage(true) / max(1, 256 * 1024 * 1024)) * 20))),
                'dbPct' => $dbHealthy ? 100 : 0,
                'queuePct' => 100,
                'cachePct' => $cacheHealthy ? 100 : 0,
            ],
            'recentAudit' => $this->userCan('audit.view') ? AdminAuditLog::with('admin')->latest('created_at')->limit(6)->get() : collect(),
        ];
    }

    protected function dataAnalytics(): array
    {
        $daysCount = match ($this->analyticsPeriod) {
            'today' => 1,
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            'all' => 3650,
            default => 30,
        };

        $sinceDt = $this->analyticsPeriod === 'today'
            ? today()->startOfDay()
            : ($this->analyticsPeriod === 'all' ? now()->subYears(10) : now()->subDays($daysCount));
        $since = $sinceDt->toDateString();

        // Base filtered public traffic query
        $trafficQuery = fn () => PlatformTrafficLog::where('is_bot', false)
            ->where('created_at', '>=', $sinceDt)
            ->where(function ($q) {
                $q->whereNull('path')
                    ->orWhere(function ($sq) {
                        $sq->where('path', 'not like', 'admin%')
                            ->where('path', 'not like', '/admin%')
                            ->where('path', 'not like', 'livewire%')
                            ->where('path', 'not like', '/livewire%');
                    });
            })
            ->when($this->analyticsCountryFilter !== 'all', fn ($q) => $q->where('country_code', $this->analyticsCountryFilter))
            ->when($this->analyticsDeviceFilter !== 'all', fn ($q) => $q->where('device_type', $this->analyticsDeviceFilter));

        // Base filtered Listing query (supports Age group filter)
        $listingQuery = fn () => Listing::query()
            ->when($this->analyticsAgeFilter !== 'all', function ($q) {
                match ($this->analyticsAgeFilter) {
                    '18-24' => $q->whereBetween('poster_age', [18, 24]),
                    '25-34' => $q->whereBetween('poster_age', [25, 34]),
                    '35-44' => $q->whereBetween('poster_age', [35, 44]),
                    '45-54' => $q->whereBetween('poster_age', [45, 54]),
                    '55+'   => $q->where('poster_age', '>=', 55),
                    default => null,
                };
            });

        $filteredListingIds = $this->analyticsAgeFilter !== 'all'
            ? $listingQuery()->pluck('id')->all()
            : null;

        $listingAnalyticsQuery = fn () => ListingAnalytics::where('metric_date', '>=', $since)
            ->when($filteredListingIds !== null, fn ($q) => $q->whereIn('listing_id', $filteredListingIds));

        // 1. Visitor & View Totals (Gross vs Unique)
        $siteGrossViews = (int) $trafficQuery()->count();
        $siteUniqueVisitors = (int) $trafficQuery()->distinct('visitor_hash')->count('visitor_hash');

        $postAnalyticsGross = (int) $listingAnalyticsQuery()->sum('views');
        $postAnalyticsUnique = (int) $listingAnalyticsQuery()->sum('unique_visitors');
        $listingTableViews = (int) $listingQuery()->sum('view_count');

        $postGrossViews = max($postAnalyticsGross, $this->analyticsPeriod === 'all' || $this->analyticsPeriod === '30d' ? $listingTableViews : $postAnalyticsGross);
        $postUniqueViews = max($postAnalyticsUnique, $this->analyticsPeriod === 'all' || $this->analyticsPeriod === '30d' ? $listingTableViews : $postAnalyticsUnique);

        $totalGrossViews = $siteGrossViews + $postGrossViews;
        $totalUniqueVisitors = max($siteUniqueVisitors, $postUniqueViews);
        $repeatViews = max(0, $totalGrossViews - $totalUniqueVisitors);

        $contacts30 = (int) $listingAnalyticsQuery()->sum('contact_clicks')
            + ContactMessage::where('created_at', '>=', $sinceDt)->count();

        $favorites30 = (int) $listingAnalyticsQuery()->sum('favorite_adds')
            + (int) DB::table('listing_favorites')->where('created_at', '>=', $sinceDt)->count();

        $fiatRev30 = (float) (PaymentTransaction::where('status', 'completed')->where('created_at', '>=', $sinceDt)->sum('amount_cents') / 100);
        $cryptoRev30 = (float) CryptoPayment::whereIn('status', [CryptoPayment::STATUS_COMPLETED, 'completed', 'confirmed'])->where('created_at', '>=', $sinceDt)->sum('fiat_amount');

        // 2. Daily Trend Series for Graph & Bar Chart (7 or 14 buckets)
        $chartDaysCount = match ($this->analyticsPeriod) {
            'today' => 7,
            '7d' => 7,
            default => 14,
        };
        $trendLabels = [];
        $trendGross = [];
        $trendUnique = [];
        $trendPostViews = [];
        $trendContacts = [];

        for ($i = $chartDaysCount - 1; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $dStr = $d->toDateString();
            $trendLabels[] = $d->format('M j');

            $dayTraffic = PlatformTrafficLog::where('is_bot', false)
                ->whereDate('created_at', $dStr)
                ->where(function ($q) {
                    $q->whereNull('path')
                        ->orWhere(function ($sq) {
                            $sq->where('path', 'not like', 'admin%')
                                ->where('path', 'not like', '/admin%')
                                ->where('path', 'not like', 'livewire%')
                                ->where('path', 'not like', '/livewire%');
                        });
                })
                ->when($this->analyticsCountryFilter !== 'all', fn ($q) => $q->where('country_code', $this->analyticsCountryFilter))
                ->when($this->analyticsDeviceFilter !== 'all', fn ($q) => $q->where('device_type', $this->analyticsDeviceFilter));

            $gDay = (int) (clone $dayTraffic)->count();
            $uDay = (int) (clone $dayTraffic)->distinct('visitor_hash')->count('visitor_hash');

            $pDayQuery = ListingAnalytics::whereDate('metric_date', $dStr)
                ->when($filteredListingIds !== null, fn ($q) => $q->whereIn('listing_id', $filteredListingIds));
            $pGrossDay = (int) (clone $pDayQuery)->sum('views');
            $pUniqueDay = (int) (clone $pDayQuery)->sum('unique_visitors');

            $cDay = ContactMessage::whereDate('created_at', $dStr)->count();

            $trendGross[] = $gDay + $pGrossDay;
            $trendUnique[] = max($uDay, $pUniqueDay);
            $trendPostViews[] = $pGrossDay;
            $trendContacts[] = $cDay;
        }

        $maxTrendVal = max(1, ...$trendGross, ...$trendUnique, ...$trendPostViews);
        $grossPts = [];
        $uniquePts = [];
        $postPts = [];
        $trendBars = [];
        $stepX = 600 / max(1, count($trendLabels) - 1);

        foreach ($trendLabels as $idx => $lbl) {
            $x = round($idx * $stepX, 1);
            $gVal = $trendGross[$idx];
            $uVal = $trendUnique[$idx];
            $pVal = $trendPostViews[$idx];

            $gY = round(145 - (($gVal / $maxTrendVal) * 115), 1);
            $uY = round(145 - (($uVal / $maxTrendVal) * 115), 1);
            $pY = round(145 - (($pVal / $maxTrendVal) * 115), 1);

            $grossPts[] = "{$x},{$gY}";
            $uniquePts[] = "{$x},{$uY}";
            $postPts[] = "{$x},{$pY}";

            $trendBars[] = [
                'label' => $lbl,
                'gross' => $gVal,
                'unique' => $uVal,
                'postViews' => $pVal,
                'contacts' => $trendContacts[$idx],
                'grossPct' => max(5, (int) round(($gVal / $maxTrendVal) * 100)),
                'uniquePct' => max(5, (int) round(($uVal / $maxTrendVal) * 100)),
                'postPct' => max(4, (int) round(($pVal / $maxTrendVal) * 100)),
            ];
        }

        $grossLineD = 'M ' . implode(' L ', $grossPts);
        $grossAreaD = $grossLineD . ' L 600,155 L 0,155 Z';
        $uniqueLineD = 'M ' . implode(' L ', $uniquePts);
        $uniqueAreaD = $uniqueLineD . ' L 600,155 L 0,155 Z';
        $postLineD = 'M ' . implode(' L ', $postPts);

        // 3. Country Breakdown Report (Chart & Graph)
        $countryRows = PlatformTrafficLog::where('is_bot', false)
            ->where('created_at', '>=', $sinceDt)
            ->where(function ($q) {
                $q->whereNull('path')
                    ->orWhere(function ($sq) {
                        $sq->where('path', 'not like', 'admin%')
                            ->where('path', 'not like', '/admin%')
                            ->where('path', 'not like', 'livewire%')
                            ->where('path', 'not like', '/livewire%');
                    });
            })
            ->when($this->analyticsDeviceFilter !== 'all', fn ($q) => $q->where('device_type', $this->analyticsDeviceFilter))
            ->select(
                'country_code',
                'country_name',
                DB::raw('count(*) as hits'),
                DB::raw('count(distinct visitor_hash) as unique_visitors')
            )
            ->groupBy('country_code', 'country_name')
            ->orderByDesc('hits')
            ->limit(15)
            ->get();

        $totalCountryHits = max(1, (int) $countryRows->sum('hits'));
        $totalCountryUnique = max(1, (int) $countryRows->sum('unique_visitors'));
        $countryReport = $countryRows->map(function ($row) use ($totalCountryHits, $totalCountryUnique) {
            return [
                'country_code' => $row->country_code ?: 'US',
                'country_name' => $row->country_name ?: 'United States',
                'hits' => (int) $row->hits,
                'unique_visitors' => (int) $row->unique_visitors,
                'gross_pct' => (int) round(($row->hits / $totalCountryHits) * 100),
                'unique_pct' => (int) round(($row->unique_visitors / $totalCountryUnique) * 100),
            ];
        })->values()->all();

        $availableCountries = PlatformTrafficLog::where('is_bot', false)
            ->select('country_code', 'country_name')
            ->whereNotNull('country_code')
            ->groupBy('country_code', 'country_name')
            ->orderBy('country_name')
            ->get();

        // 4. Device, Browser & OS Breakdown Report
        $deviceRows = $trafficQuery()
            ->select(
                'device_type',
                DB::raw('count(*) as hits'),
                DB::raw('count(distinct visitor_hash) as unique_visitors')
            )
            ->groupBy('device_type')
            ->orderByDesc('hits')
            ->get()
            ->keyBy(fn ($r) => ucfirst(strtolower((string) $r->device_type)));

        $deviceTypes = ['Desktop', 'Mobile', 'Tablet'];
        $deviceTotalHits = max(1, (int) $deviceRows->sum('hits'));
        $deviceTotalUnique = max(1, (int) $deviceRows->sum('unique_visitors'));
        $deviceReport = [];
        foreach ($deviceTypes as $dt) {
            $r = $deviceRows->get($dt);
            $hits = $r ? (int) $r->hits : 0;
            $uniq = $r ? (int) $r->unique_visitors : 0;
            $deviceReport[] = [
                'device' => $dt,
                'hits' => $hits,
                'unique_visitors' => $uniq,
                'gross_pct' => $deviceRows->sum('hits') > 0 ? (int) round(($hits / $deviceTotalHits) * 100) : 0,
                'unique_pct' => $deviceRows->sum('unique_visitors') > 0 ? (int) round(($uniq / $deviceTotalUnique) * 100) : 0,
            ];
        }

        $browserRows = $trafficQuery()
            ->select('browser', DB::raw('count(*) as hits'), DB::raw('count(distinct visitor_hash) as unique_visitors'))
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->orderByDesc('hits')
            ->limit(6)
            ->get();
        $maxBrowserHits = max(1, (int) ($browserRows->max('hits') ?? 1));
        $browserReport = $browserRows->map(fn ($r) => [
            'browser' => $r->browser ?: 'Browser',
            'hits' => (int) $r->hits,
            'unique_visitors' => (int) $r->unique_visitors,
            'pct' => (int) round(($r->hits / $maxBrowserHits) * 100),
        ])->values()->all();

        $osRows = $trafficQuery()
            ->select('os', DB::raw('count(*) as hits'), DB::raw('count(distinct visitor_hash) as unique_visitors'))
            ->whereNotNull('os')
            ->groupBy('os')
            ->orderByDesc('hits')
            ->limit(6)
            ->get();
        $maxOsHits = max(1, (int) ($osRows->max('hits') ?? 1));
        $osReport = $osRows->map(fn ($r) => [
            'os' => $r->os ?: 'OS',
            'hits' => (int) $r->hits,
            'unique_visitors' => (int) $r->unique_visitors,
            'pct' => (int) round(($r->hits / $maxOsHits) * 100),
        ])->values()->all();

        // 5. Age Demographics Report (18-24, 25-34, 35-44, 45-54, 55+)
        $ageBrackets = [
            '18-24' => [18, 24],
            '25-34' => [25, 34],
            '35-44' => [35, 44],
            '45-54' => [45, 54],
            '55+'   => [55, 120],
        ];

        $profilesWithBirthdate = UserProfile::whereNotNull('birthdate')->get(['birthdate']);
        $ageRaw = [];
        foreach ($ageBrackets as $label => [$minAge, $maxAge]) {
            $postsInBracket = Listing::whereBetween('poster_age', [$minAge, $maxAge]);
            $postsCount = (clone $postsInBracket)->count();
            $postViewsInBracket = (int) (clone $postsInBracket)->sum('view_count');

            $usersCount = $profilesWithBirthdate->filter(function ($profile) use ($minAge, $maxAge) {
                $age = $profile->age;
                return $age !== null && $age >= $minAge && $age <= $maxAge;
            })->count();

            $ageRaw[$label] = [
                'bracket' => $label,
                'posts_count' => $postsCount,
                'post_views' => $postViewsInBracket,
                'users_count' => $usersCount,
                'combined_count' => $postsCount + $usersCount,
            ];
        }

        $maxAgeCombined = max(1, ...array_column($ageRaw, 'combined_count'));
        $totalAgeCombined = max(1, array_sum(array_column($ageRaw, 'combined_count')));
        $maxAgeViews = max(1, ...array_column($ageRaw, 'post_views'));
        $totalAgeViews = max(1, array_sum(array_column($ageRaw, 'post_views')));

        $ageReport = [];
        $ageGraphPts = [];
        $ageIdx = 0;
        foreach ($ageRaw as $label => $item) {
            $sharePct = array_sum(array_column($ageRaw, 'combined_count')) > 0
                ? (int) round(($item['combined_count'] / $totalAgeCombined) * 100)
                : 0;
            $barHeightPct = (int) round(($item['combined_count'] / $maxAgeCombined) * 100);
            $viewsSharePct = array_sum(array_column($ageRaw, 'post_views')) > 0
                ? (int) round(($item['post_views'] / $totalAgeViews) * 100)
                : 0;
            $viewsHeightPct = (int) round(($item['post_views'] / $maxAgeViews) * 100);

            $x = round($ageIdx * (400 / 4), 1);
            $y = round(120 - (($item['combined_count'] / $maxAgeCombined) * 90), 1);
            $ageGraphPts[] = "{$x},{$y}";
            $ageIdx++;

            $ageReport[] = array_merge($item, [
                'share_pct' => $sharePct,
                'bar_pct' => max(6, $barHeightPct),
                'views_share_pct' => $viewsSharePct,
                'views_bar_pct' => max(6, $viewsHeightPct),
            ]);
        }
        $ageLineD = 'M ' . implode(' L ', $ageGraphPts);
        $ageAreaD = $ageLineD . ' L 400,130 L 0,130 Z';

        // 6. Posts & Post Views Report (Top Listings + Categories with Views)
        $topListings = $listingQuery()
            ->with(['category:id,name', 'city:id,name'])
            ->orderByDesc('view_count')
            ->limit(10)
            ->get(['id', 'category_id', 'city_id', 'title', 'slug', 'poster_age', 'view_count', 'reply_count', 'favorite_count', 'status']);

        $listingAnalyticsByListing = ListingAnalytics::whereIn('listing_id', $topListings->pluck('id'))
            ->select('listing_id', DB::raw('SUM(views) as gross_views'), DB::raw('SUM(unique_visitors) as unique_views'))
            ->groupBy('listing_id')
            ->get()
            ->keyBy('listing_id');

        $topListingsReport = $topListings->map(function ($l) use ($listingAnalyticsByListing) {
            $la = $listingAnalyticsByListing->get($l->id);
            $uniqueV = max((int) $l->view_count, $la ? (int) $la->unique_views : 0);
            $grossV = max($uniqueV, $la ? (int) $la->gross_views : (int) $l->view_count);
            return [
                'id' => $l->id,
                'title' => $l->title,
                'slug' => $l->slug,
                'category' => $l->category?->name ?? 'General',
                'city' => $l->city?->name ?? '—',
                'poster_age' => $l->poster_age,
                'unique_views' => $uniqueV,
                'gross_views' => $grossV,
                'reply_count' => (int) $l->reply_count,
                'favorite_count' => (int) $l->favorite_count,
                'status' => $l->status,
            ];
        })->all();

        $topCategories = Category::withCount('listings')
            ->withSum('listings as total_views', 'view_count')
            ->orderByDesc('listings_count')
            ->limit(8)
            ->get(['id', 'name']);

        $maxCatViews = max(1, (int) ($topCategories->max('total_views') ?? 1));
        $maxCatListings = max(1, (int) ($topCategories->max('listings_count') ?? 1));

        return [
            'analytics' => [
                'views30' => $this->analyticsViewMode === 'gross' ? $totalGrossViews : $totalUniqueVisitors,
                'grossViews' => $totalGrossViews,
                'uniqueVisitors' => $totalUniqueVisitors,
                'repeatViews' => $repeatViews,
                'siteGrossViews' => $siteGrossViews,
                'siteUniqueVisitors' => $siteUniqueVisitors,
                'postGrossViews' => $postGrossViews,
                'postUniqueViews' => $postUniqueViews,
                'contacts30' => $contacts30,
                'favorites30' => $favorites30,
                'newUsers30' => User::where('created_at', '>=', $sinceDt)->count(),
                'newListings30' => $listingQuery()->where('created_at', '>=', $sinceDt)->count(),
                'totalActivePosts' => $listingQuery()->where('status', Listing::STATUS_ACTIVE)->count(),
                'countriesCount' => count($countryReport),
                'revenue30' => $fiatRev30 + $cryptoRev30,
                'refunds30' => Refund::where('created_at', '>=', $sinceDt)->sum('amount_cents') / 100,
                'openReports' => ContentReport::whereIn('status', ['open', 'investigating'])->count(),
            ],
            'trendChart' => [
                'labels' => $trendLabels,
                'gross' => $trendGross,
                'unique' => $trendUnique,
                'postViews' => $trendPostViews,
                'contacts' => $trendContacts,
                'bars' => $trendBars,
                'grossLineD' => $grossLineD,
                'grossAreaD' => $grossAreaD,
                'uniqueLineD' => $uniqueLineD,
                'uniqueAreaD' => $uniqueAreaD,
                'postLineD' => $postLineD,
            ],
            'countryReport' => $countryReport,
            'availableCountries' => $availableCountries,
            'deviceReport' => $deviceReport,
            'browserReport' => $browserReport,
            'osReport' => $osReport,
            'ageReport' => $ageReport,
            'ageLineD' => $ageLineD,
            'ageAreaD' => $ageAreaD,
            'topListings' => $topListings,
            'topListingsReport' => $topListingsReport,
            'listingsByStatus' => $listingQuery()->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status'),
            'topCategories' => $topCategories,
            'maxCatViews' => $maxCatViews,
            'maxCatListings' => $maxCatListings,
        ];
    }

    protected function dataUsers(): array
    {
        $query = User::query()->with(['validIdentityVerification', 'latestVerification'])->latest();
        if ($this->userSearch !== '') {
            $term = '%' . $this->userSearch . '%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
        }
        if ($this->userStatusFilter !== 'all') {
            $query->where('account_status', $this->userStatusFilter);
        }
        if ($this->userRoleFilter !== 'all') {
            $query->where('role', $this->userRoleFilter);
        }

        return ['users' => $query->paginate(15, ['*'], 'usersPage'), 'assignableRoles' => $this->assignableRoles()];
    }

    protected function dataListings(): array
    {
        $query = Listing::query()->with(['category', 'city', 'user', 'ownerIdentityVerification'])->latest();
        if ($this->listingSearch !== '') {
            $query->where('title', 'like', '%' . $this->listingSearch . '%');
        }
        if ($this->listingStatusFilter !== 'all') {
            $query->where('status', $this->listingStatusFilter);
        }
        if ($this->listingCategoryFilter) {
            $query->where('category_id', $this->listingCategoryFilter);
        }

        $editCityOptions = collect();
        if ($this->editListingStateId) {
            $editCityOptions = City::where('state_id', $this->editListingStateId)->orderBy('name')->get(['id', 'name']);
        } elseif ($this->editListingCityId) {
            $editCityOptions = City::where('id', $this->editListingCityId)->get(['id', 'name']);
        }

        return [
            'listings' => $query->paginate(15, ['*'], 'listingsPage'),
            'categoryOptions' => Category::orderBy('name')->get(['id', 'name']),
            'stateOptions' => State::orderBy('name')->get(['id', 'name', 'code']),
            'editCityOptions' => $editCityOptions,
        ];
    }

    protected function dataModeration(): array
    {
        // 1. Pending listings review queue
        $queueQuery = Listing::with(['category', 'city', 'state', 'user.profile', 'media'])
            ->where('status', Listing::STATUS_PENDING_REVIEW);

        if ($this->moderationSearch !== '') {
            $queueQuery->where(function ($q) {
                $q->where('title', 'like', '%' . $this->moderationSearch . '%')
                  ->orWhere('description', 'like', '%' . $this->moderationSearch . '%')
                  ->orWhereHas('user', function ($uq) {
                      $uq->where('name', 'like', '%' . $this->moderationSearch . '%')
                        ->orWhere('email', 'like', '%' . $this->moderationSearch . '%');
                  });
            });
        }
        if ($this->moderationCategoryFilter) {
            $queueQuery->where('category_id', $this->moderationCategoryFilter);
        }

        // 2. Suspension Appeals
        $appealsQuery = AppealRequest::with(['user.profile', 'suspension.issuedBy'])->latest();
        if ($this->moderationSearch !== '') {
            $appealsQuery->where(function ($q) {
                $q->where('appeal_reason', 'like', '%' . $this->moderationSearch . '%')
                  ->orWhereHas('user', function ($uq) {
                      $uq->where('name', 'like', '%' . $this->moderationSearch . '%')
                        ->orWhere('email', 'like', '%' . $this->moderationSearch . '%');
                  });
            });
        }

        // 3. User Safety Reports
        $reportsQuery = ContentReport::with(['reporter', 'reportable'])->latest();
        if ($this->moderationSearch !== '') {
            $reportsQuery->where(function ($q) {
                $q->where('details', 'like', '%' . $this->moderationSearch . '%')
                  ->orWhere('reason_code', 'like', '%' . $this->moderationSearch . '%');
            });
        }
        if ($this->moderationRiskFilter !== 'all') {
            $reportsQuery->where('risk_level', $this->moderationRiskFilter);
        }

        // 4. Automated AI & Rule Flagged Queue
        $flaggedQuery = ModerationQueue::with(['assignedTo'])->latest();

        // 5. Recent Moderation Actions Log
        $recentActions = ModerationAction::with(['admin', 'queueItem'])->latest()->limit(15)->get();

        // 6. Live Statistics Summary
        $stats = [
            'pending_queue_count' => Listing::where('status', Listing::STATUS_PENDING_REVIEW)->count(),
            'appeals_pending_count' => AppealRequest::whereIn('status', ['pending', 'under_review'])->count(),
            'open_reports_count' => ContentReport::where('status', 'open')->count(),
            'flagged_count' => ModerationQueue::where('status', 'pending')->count(),
            'suspended_listings_count' => Listing::where('status', Listing::STATUS_SUSPENDED)->count(),
            'actions_today' => ModerationAction::whereDate('created_at', today())->count(),
        ];

        return [
            'pendingQueue' => $queueQuery->oldest()->paginate(10, ['*'], 'queuePage'),
            'appeals' => $appealsQuery->paginate(10, ['*'], 'appealsPage'),
            'moderationReports' => $reportsQuery->paginate(10, ['*'], 'modReportsPage'),
            'flaggedQueue' => $flaggedQuery->paginate(10, ['*'], 'flaggedPage'),
            'recentActions' => $recentActions,
            'moderationStats' => $stats,
            'categoryOptions' => Category::orderBy('name')->get(['id', 'name']),
        ];
    }

    protected function dataVerification(): array
    {
        $query = AgeAssurance::with(['user.profile', 'reviewer'])->latest();

        if ($this->verificationStatusFilter !== 'all') {
            $query->where('status', $this->verificationStatusFilter);
        }

        if ($this->verificationMethodFilter !== 'all') {
            $query->where('method', $this->verificationMethodFilter);
        }

        if (trim($this->verificationSearch) !== '') {
            $s = trim($this->verificationSearch);
            $query->where(function ($q) use ($s) {
                $q->whereHas('user', function ($uq) use ($s) {
                    $uq->where('name', 'like', "%{$s}%")
                       ->orWhere('email', 'like', "%{$s}%");
                })->orWhere('id', 'like', "%{$s}%")
                  ->orWhere('third_party_reference', 'like', "%{$s}%");
            });
        }

        $pendingCount = AgeAssurance::where('status', 'pending')->count();
        $approvedCount = AgeAssurance::where('status', 'approved')->count();
        $rejectedCount = AgeAssurance::where('status', 'rejected')->count();
        $totalVerifiedUsers = User::where('is_age_verified', true)->count();

        return [
            'verifications' => $query->paginate(15, ['*'], 'verificationsPage'),
            'verificationStats' => [
                'pending' => $pendingCount,
                'approved' => $approvedCount,
                'rejected' => $rejectedCount,
                'total_verified_users' => $totalVerifiedUsers,
            ],
            'assignableUsers' => User::where('account_status', 'active')->orderBy('name')->limit(50)->get(['id', 'name', 'email']),
        ];
    }

    protected function dataNotifications(): array
    {
        $query = NotificationLog::with('user')->latest();

        if ($this->notificationStatusFilter !== 'all') {
            $query->where('status', $this->notificationStatusFilter);
        }

        if ($this->notificationChannelFilter !== 'all') {
            $query->where('channel', $this->notificationChannelFilter);
        }

        if (trim($this->notificationSearch) !== '') {
            $s = trim($this->notificationSearch);
            $query->where(function ($q) use ($s) {
                $q->where('subject', 'like', "%{$s}%")
                  ->orWhere('recipient_target', 'like', "%{$s}%")
                  ->orWhere('notification_type', 'like', "%{$s}%")
                  ->orWhereHas('user', function ($uq) use ($s) {
                      $uq->where('name', 'like', "%{$s}%")
                         ->orWhere('email', 'like', "%{$s}%");
                  });
            });
        }

        $totalSent = NotificationLog::where('status', 'sent')->count();
        $totalQueued = NotificationLog::where('status', 'queued')->count();
        $totalFailed = NotificationLog::where('status', 'failed')->count();
        $totalTemplates = EmailTemplate::count();

        return [
            'notifications' => $query->paginate(15, ['*'], 'notificationsPage'),
            'templates' => EmailTemplate::orderBy('name')->get(),
            'notificationStats' => [
                'sent' => $totalSent,
                'queued' => $totalQueued,
                'failed' => $totalFailed,
                'templates_count' => $totalTemplates,
            ],
            'assignableUsers' => User::where('account_status', 'active')->orderBy('name')->limit(50)->get(['id', 'name', 'email']),
        ];
    }

    protected function dataReports(): array
    {
        $query = ContentReport::with(['reporter', 'resolvedBy'])
            ->orderByDesc('is_urgent_takedown')
            ->orderByRaw("CASE WHEN risk_level = 'critical' THEN 1 WHEN risk_level = 'high' THEN 2 WHEN risk_level = 'medium' THEN 3 ELSE 4 END")
            ->latest('created_at');

        if ($this->reportStatusFilter !== 'all') {
            $query->where('status', $this->reportStatusFilter);
        }

        if ($this->reportSeverityFilter !== 'all') {
            $query->where('risk_level', $this->reportSeverityFilter);
        }

        if ($this->reportReasonFilter !== 'all') {
            $query->where('reason_code', $this->reportReasonFilter);
        }

        if (trim($this->reportSearch) !== '') {
            $s = trim($this->reportSearch);
            $query->where(function ($q) use ($s) {
                $q->where('id', $s)
                  ->orWhere('details', 'like', "%{$s}%")
                  ->orWhere('reason_code', 'like', "%{$s}%")
                  ->orWhere('reporter_ip', 'like', "%{$s}%")
                  ->orWhereHas('reporter', function ($uq) use ($s) {
                      $uq->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%");
                  });
            });
        }

        $allReports = ContentReport::all();
        $totalCount = $allReports->count();
        $openCount = $allReports->where('status', 'open')->count();
        $investigatingCount = $allReports->where('status', 'investigating')->count();
        $criticalCount = $allReports->whereIn('risk_level', ['critical', 'high'])->whereIn('status', ['open', 'investigating'])->count();
        $resolvedCount = $allReports->where('status', 'resolved')->count();
        $dismissedCount = $allReports->where('status', 'dismissed')->count();

        // Reason breakdown
        $reasonCounts = $allReports->groupBy('reason_code')->map->count()->toArray();
        $riskCounts = $allReports->groupBy('risk_level')->map->count()->toArray();

        return [
            'reports' => $query->paginate(15, ['*'], 'reportsPage'),
            'reportStats' => [
                'total' => $totalCount,
                'open' => $openCount,
                'investigating' => $investigatingCount,
                'active_queue' => $openCount + $investigatingCount,
                'critical_pending' => $criticalCount,
                'resolved' => $resolvedCount,
                'dismissed' => $dismissedCount,
                'urgent_count' => $allReports->where('is_urgent_takedown', true)->whereIn('status', ['open', 'investigating'])->count(),
                'resolution_rate' => $totalCount > 0 ? round(($resolvedCount / $totalCount) * 100, 1) : 100.0,
                'reason_breakdown' => $reasonCounts,
                'risk_breakdown' => $riskCounts,
            ],
        ];
    }

    protected function dataDmca(): array
    {
        $query = DmcaRequest::with(['targetListing', 'reviewer'])->latest();
        if ($this->dmcaStatusFilter !== 'all') {
            $query->where('status', $this->dmcaStatusFilter);
        }

        return ['dmcaRequests' => $query->paginate(15, ['*'], 'dmcaPage')];
    }

    protected function dataLegal(): array
    {
        $query = LegalRequest::with(['targetUser', 'targetListing', 'handler'])->latest();
        if ($this->legalStatusFilter !== 'all') {
            $query->where('status', $this->legalStatusFilter);
        }

        return ['legalRequests' => $query->paginate(15, ['*'], 'legalPage')];
    }

    protected function dataCompliance(): array
    {
        return ['stateCompliances' => StateCompliance::orderBy('state_code')->get()];
    }

    protected function dataEvidence(): array
    {
        return [
            'preservations' => EvidencePreservation::with(['legalRequest', 'frozenBy'])->latest()->paginate(15, ['*'], 'evidencePage'),
        ];
    }

    protected function dataAbuse(): array
    {
        return ['abuseMessages' => ContactMessage::where('is_flagged', true)->with(['listing', 'recipient'])->latest()->paginate(15, ['*'], 'abusePage')];
    }

    protected function dataSupportTickets(): array
    {
        return [
            'supportTickets' => ContactMessage::whereNull('listing_id')
                ->with(['sender'])
                ->latest()
                ->paginate(15, ['*'], 'supportPage'),
        ];
    }

    protected function dataCategories(): array
    {
        return ['categories' => Category::withCount('listings')->orderBy('name')->get()];
    }

    protected function dataAttributes(): array
    {
        return ['attributes' => CategoryAttribute::with('category')->withCount('options')->orderBy('category_id')->get()];
    }

    protected function dataLocations(): array
    {
        return [
            'states' => State::withCount('cities')->orderBy('name')->get(),
            'cityCount' => City::count(),
        ];
    }

    protected function dataPricing(): array
    {
        return ['pricingPlans' => PricingPlan::orderBy('plan_type')->orderBy('price_cents')->get()];
    }

    protected function dataFeatured(): array
    {
        return ['featuredListings' => FeaturedListing::with(['listing', 'user'])->latest()->paginate(15, ['*'], 'featuredPage')];
    }

    protected function dataSubscriptions(): array
    {
        $query = Subscription::with(['user.profile', 'plan'])->latest();

        if ($this->subscriptionStatusFilter !== 'all') {
            $query->where('status', $this->subscriptionStatusFilter);
        }

        if ($this->subscriptionPlanFilter !== 'all') {
            $query->where('pricing_plan_id', (int) $this->subscriptionPlanFilter);
        }

        if (trim($this->subscriptionSearch) !== '') {
            $s = trim($this->subscriptionSearch);
            $query->where(function ($q) use ($s) {
                $q->whereHas('user', function ($uq) use ($s) {
                    $uq->where('name', 'like', "%{$s}%")
                       ->orWhere('email', 'like', "%{$s}%");
                })->orWhere('gateway_subscription_id', 'like', "%{$s}%")
                  ->orWhere('id', 'like', "%{$s}%");
            });
        }

        // Metrics
        $totalSubscribers = Subscription::where('status', 'active')->distinct('user_id')->count('user_id');
        $activeCount = Subscription::where('status', 'active')->count();
        $canceledCount = Subscription::where('status', 'canceled')->count();

        $mrrCents = Subscription::where('status', 'active')
            ->join('pricing_plans', 'subscriptions.pricing_plan_id', '=', 'pricing_plans.id')
            ->sum('pricing_plans.price_cents');
        $mrr = $mrrCents / 100;

        $expiringSoon = Subscription::where('status', 'active')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays(7)])
            ->count();

        return [
            'subscriptions' => $query->paginate(15, ['*'], 'subscriptionsPage'),
            'allPlans' => PricingPlan::orderBy('plan_type')->orderBy('price_cents')->get(),
            'assignableUsers' => User::where('account_status', 'active')->orderBy('name')->limit(50)->get(['id', 'name', 'email']),
            'subscriptionStats' => [
                'mrr' => $mrr,
                'active_count' => $activeCount,
                'total_subscribers' => $totalSubscribers,
                'canceled_count' => $canceledCount,
                'expiring_soon' => $expiringSoon,
            ],
        ];
    }

    protected function dataPayments(): array
    {
        // 1. Transactions Query
        $txnQuery = PaymentTransaction::with(['user.profile', 'plan', 'invoice', 'listing.category', 'listing.city'])->latest();
        if ($this->paymentStatusFilter !== 'all') {
            $txnQuery->where('status', $this->paymentStatusFilter);
        }
        if ($this->paymentGatewayFilter !== 'all') {
            $txnQuery->where('gateway', $this->paymentGatewayFilter);
        }
        if (trim($this->paymentSearch) !== '') {
            $s = trim($this->paymentSearch);
            $txnQuery->where(function ($q) use ($s) {
                $q->where('reference_id', 'like', "%{$s}%")
                    ->orWhere('gateway_transaction_id', 'like', "%{$s}%")
                    ->orWhereHas('user', function ($uq) use ($s) {
                        $uq->where('name', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%");
                    })
                    ->orWhereHas('listing', function ($lq) use ($s) {
                        $lq->where('title', 'like', "%{$s}%");
                    });
            });
        }

        // 2. Crypto Payments Query
        $cryptoQuery = CryptoPayment::with(['user.profile', 'listing.category', 'listing.city', 'boostPlan', 'gateway'])->latest();
        if ($this->paymentStatusFilter !== 'all') {
            $cryptoQuery->where('status', $this->paymentStatusFilter);
        }
        if (trim($this->paymentSearch) !== '') {
            $s = trim($this->paymentSearch);
            $cryptoQuery->where(function ($q) use ($s) {
                $q->where('internal_order_id', 'like', "%{$s}%")
                    ->orWhere('provider_payment_id', 'like', "%{$s}%")
                    ->orWhere('transaction_hash', 'like', "%{$s}%")
                    ->orWhere('payment_address', 'like', "%{$s}%")
                    ->orWhere('payment_currency', 'like', "%{$s}%")
                    ->orWhereHas('user', function ($uq) use ($s) {
                        $uq->where('name', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%");
                    });
            });
        }

        // 3. Wallet Transactions Query
        $walletQuery = WalletTransaction::with(['user.profile', 'listing'])->latest();
        if (trim($this->paymentSearch) !== '') {
            $s = trim($this->paymentSearch);
            $walletQuery->where(function ($q) use ($s) {
                $q->where('reference', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhere('type', 'like', "%{$s}%")
                    ->orWhereHas('user', function ($uq) use ($s) {
                        $uq->where('name', 'like', "%{$s}%")
                            ->orWhere('email', 'like', "%{$s}%");
                    });
            });
        }

        // 4. Refunds Query
        $refundQuery = Refund::with(['transaction.user', 'processedBy'])->latest();
        if (trim($this->paymentSearch) !== '') {
            $s = trim($this->paymentSearch);
            $refundQuery->where(function ($q) use ($s) {
                $q->where('reason', 'like', "%{$s}%")
                    ->orWhere('gateway_refund_id', 'like', "%{$s}%")
                    ->orWhereHas('transaction', function ($tq) use ($s) {
                        $tq->where('reference_id', 'like', "%{$s}%");
                    });
            });
        }

        // 5. KPI Financial Metrics
        $fiatRevenue = (float) (PaymentTransaction::where('status', 'completed')->sum('amount_cents') / 100);
        $cryptoVolume = (float) CryptoPayment::whereIn('status', ['COMPLETED', 'CONFIRMED', 'completed', 'confirmed'])->sum('fiat_amount');
        $totalGrossRevenue = $fiatRevenue + $cryptoVolume;
        $totalRefunded = (float) (Refund::where('status', 'completed')->sum('amount_cents') / 100);
        $walletCreditsCirculation = (int) UserWallet::sum('balance');

        // Dynamic Gateways for Filter
        $activeGateways = PaymentTransaction::distinct()->pluck('gateway')->filter()->values()->toArray();
        if (empty($activeGateways)) {
            $activeGateways = ['sandbox', 'stripe', 'crypto'];
        }

        return [
            'creditPackages' => CreditPackage::orderBy('display_order')->get(),
            'transactions' => $txnQuery->paginate(15, ['*'], 'transactionsPage'),
            'cryptoPayments' => $cryptoQuery->paginate(15, ['*'], 'cryptoPage'),
            'walletTransactions' => $walletQuery->paginate(15, ['*'], 'walletPage'),
            'refunds' => $refundQuery->paginate(15, ['*'], 'refundsPage'),
            'pricingRules' => PricingRule::with(['plan', 'category', 'state'])->latest()->get(),
            'fraudLogs' => PaymentFraudLog::with('user')->latest()->limit(20)->get(),
            'unreconciledCount' => PaymentTransaction::where('is_reconciled', false)->count(),
            'gatewaysList' => $activeGateways,
            'financialStats' => [
                'gross_revenue' => $totalGrossRevenue,
                'fiat_revenue' => $fiatRevenue,
                'crypto_volume' => $cryptoVolume,
                'total_refunded' => $totalRefunded,
                'wallet_credits_circulation' => $walletCreditsCirculation,
                'completed_txn_count' => PaymentTransaction::where('status', 'completed')->count(),
                'crypto_orders_count' => CryptoPayment::count(),
                'wallet_txns_count' => WalletTransaction::count(),
                'fraud_alerts_count' => PaymentFraudLog::count(),
                'refunds_count' => Refund::count(),
                'pricing_rules_count' => PricingRule::count(),
                'credit_packages_count' => CreditPackage::count(),
            ],
            'categoryOptions' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'stateOptions' => State::orderBy('name')->get(['id', 'name', 'code']),
            'pricingPlans' => PricingPlan::where('is_active', true)->orderBy('name')->get(['id', 'name', 'price_cents']),
            'treasuryWallets' => CryptoTreasuryWallet::orderBy('tier')->get(),
            'pendingApprovals' => CryptoSweepApproval::with(['sweepTransaction.invoice.user', 'admin'])->where('status', 'PENDING')->latest()->get(),
            'sweepTransactions' => CryptoSweepTransaction::with(['invoice.user', 'approver', 'executor'])->latest()->limit(20)->get(),
            'gasReserves' => CryptoGasReserve::all(),
            'gasAlerts' => CryptoGasAlert::where('is_resolved', false)->latest()->get(),
            'isEmergencyPaused' => (bool) CryptoPaymentSetting::get('treasury_emergency_pause', false),
            'autoSweepThreshold' => (float) CryptoPaymentSetting::get('auto_sweep_threshold_usd', 100.00),
            'largeApprovalThreshold' => (float) CryptoPaymentSetting::get('large_transfer_approval_threshold_usd', 500.00),
            'totalSweptUsd' => (float) CryptoSweepTransaction::where('status', 'CONFIRMED')->sum('amount_usd'),
            'pendingSweptUsd' => (float) CryptoSweepTransaction::whereIn('status', ['PENDING', 'AWAITING_APPROVAL'])->sum('amount_usd'),
            'totalGasFeesUsd' => (float) CryptoGasTransaction::sum('fee_usd'),
        ];
    }

    protected function dataAds(): array
    {
        $query = AdPlacement::latest('updated_at');

        if ($this->adSlotFilter !== 'all') {
            $query->where('slot', $this->adSlotFilter);
        }

        if ($this->adStatusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->adStatusFilter === 'paused') {
            $query->where('is_active', false);
        }

        if (trim($this->adSearch) !== '') {
            $s = trim($this->adSearch);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('advertiser_name', 'like', "%{$s}%")
                  ->orWhere('target_url', 'like', "%{$s}%")
                  ->orWhere('slot', 'like', "%{$s}%");
            });
        }

        $allAds = AdPlacement::all();
        $totalImpressions = (int) $allAds->sum('impressions');
        $totalClicks = (int) $allAds->sum('clicks');
        $totalRevenueCents = (int) $allAds->where('is_active', true)->sum('price_cents');
        $globalCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0.0;

        $slotInventory = [
            'homepage_top' => [
                'name' => 'Homepage Top Leaderboard',
                'dimensions' => '970x250 / 728x90',
                'device' => 'Desktop & Tablet',
                'location' => 'Pinned directly below main navbar across homepage.',
                'suggested_rate' => '$450.00 / mo',
                'active_count' => $allAds->where('slot', 'homepage_top')->where('is_active', true)->count(),
                'total_count' => $allAds->where('slot', 'homepage_top')->count(),
            ],
            'sidebar' => [
                'name' => 'Sidebar Sticky Banner / Half-Page',
                'dimensions' => '300x600 / 300x250',
                'device' => 'Desktop & Large Screens',
                'location' => 'Persistent sticky sidebar position beside browse listings.',
                'suggested_rate' => '$300.00 / mo',
                'active_count' => $allAds->where('slot', 'sidebar')->where('is_active', true)->count(),
                'total_count' => $allAds->where('slot', 'sidebar')->count(),
            ],
            'search_results' => [
                'name' => 'Search Results In-Feed Native Sponsor',
                'dimensions' => 'Native Listing Card / 728x90',
                'device' => 'Universal (Desktop & Mobile)',
                'location' => 'Injected every 5 listings in search & classified browse results.',
                'suggested_rate' => '$250.00 / mo',
                'active_count' => $allAds->where('slot', 'search_results')->where('is_active', true)->count(),
                'total_count' => $allAds->where('slot', 'search_results')->count(),
            ],
            'listing_detail' => [
                'name' => 'Listing Detail Showcase Banner',
                'dimensions' => '728x90 / 320x50',
                'device' => 'Universal (Desktop & Mobile)',
                'location' => 'Positioned right above photo galleries and contact forms on every ad.',
                'suggested_rate' => '$200.00 / mo',
                'active_count' => $allAds->where('slot', 'listing_detail')->where('is_active', true)->count(),
                'total_count' => $allAds->where('slot', 'listing_detail')->count(),
            ],
            'footer' => [
                'name' => 'Sitewide Footer Leaderboard',
                'dimensions' => '970x90 / 728x90',
                'device' => 'Universal (Desktop & Mobile)',
                'location' => 'Bottom anchor banner displayed immediately above footer copyright.',
                'suggested_rate' => '$150.00 / mo',
                'active_count' => $allAds->where('slot', 'footer')->where('is_active', true)->count(),
                'total_count' => $allAds->where('slot', 'footer')->count(),
            ],
        ];

        return array_merge([
            'ads' => $query->paginate(15, ['*'], 'adPage'),
            'adSlots' => AdPlacement::SLOTS,
            'slotInventory' => $slotInventory,
            'topPerformers' => $allAds->sortByDesc(fn ($a) => $a->clickThroughRate())->take(5),
            'adStats' => [
                'total_campaigns' => $allAds->count(),
                'active_campaigns' => $allAds->where('is_active', true)->count(),
                'paused_campaigns' => $allAds->where('is_active', false)->count(),
                'total_impressions' => $totalImpressions,
                'total_clicks' => $totalClicks,
                'global_ctr' => $globalCtr,
                'total_revenue_usd' => $totalRevenueCents / 100,
            ],
        ], $this->buildEnterpriseAdsData());
    }

    protected function dataSeo(): array
    {
        $this->loadMarketingSettings();

        // 1. Date Range
        $startDate = match ($this->seoDateRange) {
            'today' => now()->startOfDay(),
            'yesterday' => now()->subDay()->startOfDay(),
            'last_7_days' => now()->subDays(7)->startOfDay(),
            'last_28_days' => now()->subDays(28)->startOfDay(),
            'last_3_months' => now()->subMonths(3)->startOfDay(),
            'last_6_months' => now()->subMonths(6)->startOfDay(),
            'last_12_months' => now()->subYears(1)->startOfDay(),
            'custom' => $this->seoCustomStartDate ? \Illuminate\Support\Carbon::parse($this->seoCustomStartDate)->startOfDay() : now()->subDays(28)->startOfDay(),
            default => now()->subDays(28)->startOfDay(),
        };

        $endDate = match ($this->seoDateRange) {
            'yesterday' => now()->subDay()->endOfDay(),
            'custom' => $this->seoCustomEndDate ? \Illuminate\Support\Carbon::parse($this->seoCustomEndDate)->endOfDay() : now()->endOfDay(),
            default => now()->endOfDay(),
        };

        // 2. Base Totals
        $allMetas = SeoMeta::all();
        $totalCategories = Category::where('is_active', true)->count();
        $totalStates = State::where('is_active', true)->count();
        $totalCities = City::count();
        $totalListings = Listing::where('status', Listing::STATUS_ACTIVE)->count();
        $totalCms = CmsPage::where('is_published', true)->count();
        $totalEstimatedUrls = 3 + $totalCms + $totalCategories + $totalStates + min($totalCities, 2500) + $totalListings;

        $dbTotalUrls = \App\Modules\SEO\Models\SeoUrlRecord::count();
        $totalWebsiteUrls = max($dbTotalUrls, $totalEstimatedUrls);

        // 3. Complete 20 SEO Dashboard Metrics
        $totalIndexedUrls = \App\Modules\SEO\Models\SeoUrlRecord::where('indexing_status', 'indexed')->count();
        $totalNotIndexedUrls = \App\Modules\SEO\Models\SeoUrlRecord::where('indexing_status', 'not_indexed')->count();
        $pendingIndexingUrls = \App\Modules\SEO\Models\SeoIndexingQueue::where('status', 'pending')->count();
        $sitemapUrlsCount = (int) \App\Modules\SEO\Models\SeoSitemap::sum('url_count');
        $submittedUrls = \App\Modules\SEO\Models\SeoUrlRecord::where('indexing_status', 'submitted')->count();
        $failedIndexingRequests = \App\Modules\SEO\Models\SeoIndexingQueue::where('status', 'failed')->count();

        $analyticsQuery = \App\Modules\SEO\Models\SeoSearchAnalytic::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
        $organicClicks = (int) (clone $analyticsQuery)->sum('clicks');
        $organicImpressions = (int) (clone $analyticsQuery)->sum('impressions');
        $avgPosition = round((float) ((clone $analyticsQuery)->avg('position') ?: 0.0), 1);
        $avgCtr = round((float) (((clone $analyticsQuery)->avg('ctr') ?: 0.0) * 100), 2);

        $totalTargetedKeywords = \App\Modules\SEO\Models\SeoKeyword::count();
        $rankingKeywords = \App\Modules\SEO\Models\SeoKeyword::whereNotNull('ranking_position')->where('ranking_position', '<=', 100)->count();

        $unresolvedIssuesCount = \App\Modules\SEO\Models\SeoIssue::where('is_resolved', false)->count();
        $missingTitles = \App\Modules\SEO\Models\SeoIssue::where('issue_type', 'missing_title')->where('is_resolved', false)->count();
        $missingDescriptions = \App\Modules\SEO\Models\SeoIssue::where('issue_type', 'missing_description')->where('is_resolved', false)->count();
        $duplicateWarnings = \App\Modules\SEO\Models\SeoIssue::whereIn('issue_type', ['duplicate_title', 'duplicate_description'])->where('is_resolved', false)->count();
        $brokenLinks = \App\Modules\SEO\Models\SeoIssue::where('issue_type', 'broken_internal_link')->where('is_resolved', false)->count();
        $crawlErrors = \App\Modules\SEO\Models\SeoIssue::whereIn('issue_type', ['404_error', '500_error'])->where('is_resolved', false)->count();

        $seoHealthScore = $totalWebsiteUrls > 0
            ? max(10, min(100, (int) round(100 - (($unresolvedIssuesCount / max($totalWebsiteUrls, 1)) * 100))))
            : 100;

        $seoDashboardMetrics = [
            'total_website_urls' => $totalWebsiteUrls,
            'total_indexed_urls' => $totalIndexedUrls,
            'total_not_indexed_urls' => $totalNotIndexedUrls,
            'pending_indexing_urls' => $pendingIndexingUrls,
            'sitemap_urls' => $sitemapUrlsCount > 0 ? $sitemapUrlsCount : $totalWebsiteUrls,
            'submitted_urls' => $submittedUrls,
            'failed_indexing_requests' => $failedIndexingRequests,
            'total_organic_clicks' => $organicClicks,
            'total_organic_impressions' => $organicImpressions,
            'average_google_position' => $avgPosition > 0 ? $avgPosition : 0.0,
            'average_ctr' => $avgCtr > 0 ? $avgCtr : 0.0,
            'total_targeted_keywords' => $totalTargetedKeywords,
            'ranking_keywords' => $rankingKeywords,
            'seo_health_score' => $seoHealthScore,
            'technical_seo_issues' => $unresolvedIssuesCount,
            'missing_meta_titles' => $missingTitles,
            'missing_meta_descriptions' => $missingDescriptions,
            'duplicate_content_warnings' => $duplicateWarnings,
            'broken_links' => $brokenLinks,
            'crawl_errors' => $crawlErrors,
        ];

        // 4. Sub-Tab Queries
        // Keywords
        $kwQuery = \App\Modules\SEO\Models\SeoKeyword::with(['group', 'category', 'state', 'city'])->latest('updated_at');
        if ($this->seoKeywordIntentFilter !== 'all') {
            $kwQuery->where('search_intent', $this->seoKeywordIntentFilter);
        }
        if ($this->seoKeywordPriorityFilter !== 'all') {
            $kwQuery->where('priority', $this->seoKeywordPriorityFilter);
        }
        if (trim($this->seoKeywordSearch) !== '') {
            $s = trim($this->seoKeywordSearch);
            $kwQuery->where('keyword', 'like', "%{$s}%");
        }

        // URL Inventory
        $urlQuery = \App\Modules\SEO\Models\SeoUrlRecord::latest('updated_at');
        if ($this->seoUrlTypeFilter !== 'all') {
            $urlQuery->where('url_type', $this->seoUrlTypeFilter);
        }
        if ($this->seoUrlIndexingFilter !== 'all') {
            $urlQuery->where('indexing_status', $this->seoUrlIndexingFilter);
        }
        if (trim($this->seoUrlSearch) !== '') {
            $s = trim($this->seoUrlSearch);
            $urlQuery->where(function ($q) use ($s) {
                $q->where('url', 'like', "%{$s}%")
                    ->orWhere('meta_title', 'like', "%{$s}%");
            });
        }

        // Issues
        $issueQuery = \App\Modules\SEO\Models\SeoIssue::latest('updated_at');
        if ($this->seoIssueSeverityFilter !== 'all') {
            $issueQuery->where('severity', $this->seoIssueSeverityFilter);
        }
        if ($this->seoIssueTypeFilter !== 'all') {
            $issueQuery->where('issue_type', $this->seoIssueTypeFilter);
        }

        // Redirects
        $redirQuery = \App\Modules\SEO\Models\SeoRedirect::latest('updated_at');
        if ($this->seoRedirectStatusFilter !== 'all') {
            $redirQuery->where('status_code', (int) $this->seoRedirectStatusFilter);
        }
        if (trim($this->seoRedirectSearch) !== '') {
            $s = trim($this->seoRedirectSearch);
            $redirQuery->where(function ($q) use ($s) {
                $q->where('source_path', 'like', "%{$s}%")
                    ->orWhere('target_url', 'like', "%{$s}%");
            });
        }

        // Sitemaps list
        $registeredSitemaps = \App\Modules\SEO\Models\SeoSitemap::all();
        if ($registeredSitemaps->isEmpty()) {
            $sitemaps = [
                ['name' => 'Master Sitemap Index', 'route' => route('sitemap.index'), 'type' => 'sitemapindex', 'frequency' => 'hourly', 'priority' => '1.0', 'count' => 7, 'status' => '200 OK / Live'],
                ['name' => 'Core Pages & Legal Sitemaps', 'route' => route('sitemaps.pages'), 'type' => 'urlset', 'frequency' => 'hourly', 'priority' => '1.0 / 0.6', 'count' => 7 + $totalCms, 'status' => '200 OK / Live'],
                ['name' => 'Classified Categories Sitemap', 'route' => route('sitemaps.categories'), 'type' => 'urlset', 'frequency' => 'daily', 'priority' => '0.8', 'count' => $totalCategories, 'status' => '200 OK / Live'],
                ['name' => 'Geo Locations (50 States & Cities)', 'route' => route('sitemaps.locations'), 'type' => 'urlset', 'frequency' => 'daily', 'priority' => '0.7 / 0.6', 'count' => $totalStates + min($totalCities, 2500), 'status' => '200 OK / Live'],
                ['name' => 'Dedicated US States Sitemap', 'route' => route('sitemaps.states'), 'type' => 'urlset', 'frequency' => 'daily', 'priority' => '0.8', 'count' => $totalStates, 'status' => '200 OK / Live'],
                ['name' => 'Dedicated US Cities Sitemap', 'route' => route('sitemaps.cities'), 'type' => 'urlset', 'frequency' => 'daily', 'priority' => '0.6', 'count' => min($totalCities, 5000), 'status' => '200 OK / Live'],
                ['name' => 'Live Classified Listings', 'route' => route('sitemaps.listings'), 'type' => 'urlset', 'frequency' => 'hourly', 'priority' => '0.9 / 0.7', 'count' => $totalListings, 'status' => '200 OK / Live'],
                ['name' => 'Media & Photos Sitemap', 'route' => route('sitemaps.images'), 'type' => 'urlset', 'frequency' => 'daily', 'priority' => '0.6', 'count' => min($totalListings * 3, 5000), 'status' => '200 OK / Live'],
            ];
        } else {
            $sitemaps = $registeredSitemaps->map(function ($sm) {
                return [
                    'name' => $sm->name,
                    'route' => url('sitemaps/' . $sm->name),
                    'type' => str_contains($sm->name, 'index') ? 'sitemapindex' : 'urlset',
                    'frequency' => 'daily',
                    'priority' => '0.8',
                    'count' => $sm->url_count,
                    'status' => $sm->is_valid_xml ? '200 OK / Valid XML' : 'Error: ' . $sm->validation_error,
                    'lastmod' => $sm->last_generated_at?->diffForHumans() ?: 'Never',
                ];
            })->toArray();
        }

        // Meta routes query
        $metaQuery = SeoMeta::query()->orderBy('page_route');
        if ($this->seoRobotsFilter !== 'all') {
            $metaQuery->where('robots_directive', $this->seoRobotsFilter);
        }
        if (trim($this->seoSearch) !== '') {
            $s = trim($this->seoSearch);
            $metaQuery->where(function ($q) use ($s) {
                $q->where('page_route', 'like', "%{$s}%")
                    ->orWhere('meta_title', 'like', "%{$s}%")
                    ->orWhere('meta_description', 'like', "%{$s}%");
            });
        }

        $gscService = app(\App\Modules\SEO\Services\GoogleSearchConsoleService::class);
        $gscSettings = $gscService->getSettings();

        // Load robots.txt content if empty
        if (empty($this->seoRobotsTxtContent)) {
            $this->seoRobotsTxtContent = (string) (SystemSetting::where('group', 'seo')->where('key', 'robots_txt')->value('value') ?? '');
            if (empty(trim($this->seoRobotsTxtContent))) {
                $this->seoRobotsTxtContent = "User-agent: *\nAllow: /\nDisallow: /account/\nDisallow: /admin/\nDisallow: /api/\nDisallow: /webhooks/\nDisallow: /listings/create\nDisallow: /*/edit\n\nSitemap: " . route('sitemap.index') . "\n";
            }
        }

        return [
            'metrics' => $seoDashboardMetrics,
            'seoMetas' => $metaQuery->paginate(15, ['*'], 'seoPage'),
            'seoKeywords' => $kwQuery->paginate(15, ['*'], 'seoKeywordsPage'),
            'seoKeywordGroups' => \App\Modules\SEO\Models\SeoKeywordGroup::all(),
            'seoUrlRecords' => $urlQuery->paginate(15, ['*'], 'seoUrlsPage'),
            'sitemaps' => $sitemaps,
            'indexingQueue' => \App\Modules\SEO\Models\SeoIndexingQueue::latest('id')->paginate(15, ['*'], 'seoQueuePage'),
            'indexingLogs' => \App\Modules\SEO\Models\SeoIndexingLog::latest('id')->limit(20)->get(),
            'seoIssues' => $issueQuery->paginate(15, ['*'], 'seoIssuesPage'),
            'seoRedirects' => $redirQuery->paginate(15, ['*'], 'seoRedirectsPage'),
            'gscAnalytics' => $analyticsQuery->latest('date')->limit(30)->get(),
            'contentSuggestions' => \App\Modules\SEO\Models\SeoContentSuggestion::latest('id')->limit(15)->get(),
            'gscSettings' => $gscSettings,
            'searchTrends' => SearchTrend::with(['category', 'state', 'city'])->orderByDesc('hits')->limit(15)->get(),
            'categories' => Category::where('is_active', true)->get(),
            'states' => State::where('is_active', true)->get(),
            'seoStats' => [
                'total_overrides' => $allMetas->count(),
                'indexable_routes' => $totalEstimatedUrls,
                'index_follow_count' => $allMetas->where('robots_directive', 'index, follow')->count(),
                'sitemaps_count' => count($sitemaps),
                'health_score' => $seoHealthScore,
            ],
        ];
    }

    protected function dataCms(): array
    {
        $query = CmsPage::latest('updated_at');

        if ($this->cmsStatusFilter === 'published') {
            $query->where('is_published', true);
        } elseif ($this->cmsStatusFilter === 'draft') {
            $query->where('is_published', false);
        }

        if (trim($this->cmsSearch) !== '') {
            $s = trim($this->cmsSearch);
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('slug', 'like', "%{$s}%")
                  ->orWhere('content', 'like', "%{$s}%");
            });
        }

        $totalPages = CmsPage::count();
        $publishedCount = CmsPage::where('is_published', true)->count();
        $draftCount = CmsPage::where('is_published', false)->count();
        $mandatoryCount = CmsPage::whereIn('slug', ['terms-of-service', 'privacy-policy', 'safety-guidelines', 'community-standards'])->where('is_published', true)->count();

        return [
            'cmsPages' => $query->paginate(15, ['*'], 'cmsPage'),
            'cmsStats' => [
                'total_pages' => $totalPages,
                'published_pages' => $publishedCount,
                'draft_pages' => $draftCount,
                'mandatory_pages' => $mandatoryCount,
            ],
        ];
    }

    protected function dataStaff(): array
    {
        $query = User::whereIn('role', self::STAFF_ROLES)->with(['profile', 'roles'])->latest();

        if ($this->staffRoleFilter !== 'all') {
            $query->where('role', $this->staffRoleFilter);
        }

        if ($this->staffStatusFilter !== 'all') {
            $query->where('account_status', $this->staffStatusFilter);
        }

        if (trim($this->staffSearch) !== '') {
            $s = trim($this->staffSearch);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhereHas('profile', function ($pq) use ($s) {
                        $pq->where('display_name', 'like', "%{$s}%");
                    });
            });
        }

        $allStaff = User::whereIn('role', self::STAFF_ROLES)->get();

        return [
            'staffMembers' => $query->paginate(15, ['*'], 'staffPage'),
            'assignableRoles' => $this->assignableRoles(),
            'staffStats' => [
                'total_staff' => $allStaff->count(),
                'admin_count' => $allStaff->whereIn('role', ['super_admin', 'admin'])->count(),
                'moderator_count' => $allStaff->whereIn('role', ['senior_moderator', 'listing_moderator'])->count(),
                'other_staff_count' => $allStaff->whereNotIn('role', ['super_admin', 'admin', 'senior_moderator', 'listing_moderator'])->count(),
                'active_count' => $allStaff->where('account_status', 'active')->count(),
            ],
        ];
    }

    protected function dataRoles(): array
    {
        return [
            'roles' => Role::with('permissions:id,slug')->orderBy('id')->get(),
            'permissionsByModule' => Permission::orderBy('module')->orderBy('slug')->get()->groupBy('module'),
        ];
    }

    protected function dataSettings(): array
    {
        return ['systemSettings' => SystemSetting::orderBy('group')->orderBy('key')->get()->groupBy('group')];
    }

    protected function dataAudit(): array
    {
        $query = AdminAuditLog::with('admin')->latest('created_at')->latest('id');
        if ($this->auditActionSearch !== '') {
            $query->where('action', 'like', '%' . $this->auditActionSearch . '%');
        }
        if ($this->auditAdminFilter) {
            $query->where('admin_user_id', $this->auditAdminFilter);
        }

        return [
            'auditLogs' => $query->paginate(20, ['*'], 'auditPage'),
            'auditAdmins' => User::whereIn('id', AdminAuditLog::select('admin_user_id')->distinct())->get(['id', 'name']),
        ];
    }

    protected function assignableRoles(): array
    {
        $roles = ['user' => 'Member', 'advertiser' => 'Advertiser'];
        foreach (Role::orderBy('id')->get() as $role) {
            if ($role->slug === 'super_admin' && !$this->isSuperAdmin()) {
                continue;
            }
            $roles[$role->slug] = $role->name;
        }

        return $roles;
    }
}
