<?php

use App\Http\Controllers\Agency\CertificationController;
use App\Http\Controllers\Agency\CoverageController;
use App\Http\Controllers\Agency\DocumentController;
use App\Http\Controllers\Agency\HourController;
use App\Http\Controllers\Agency\LeadInboxController;
use App\Http\Controllers\Agency\MediaController;
use App\Http\Controllers\Agency\PricingController;
use App\Http\Controllers\Agency\ProfileController as AgencyProfileController;
use App\Http\Controllers\Agency\ServiceController;
use App\Http\Controllers\Agency\SettingsController;
use App\Http\Controllers\Agency\StaffController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AgencyDashboardController;
use App\Http\Controllers\AgencyRegistrationController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Middleware\ResolveWorkspace;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
});

// Public agency directory — no auth required, browsable by families.
Route::prefix('agencies')->name('agencies.')->group(function () {
    Route::get('/', [AgencyController::class, 'index'])->name('index');
    Route::get('/{agency:slug}', [AgencyController::class, 'show'])->name('show');
});

/*
|--------------------------------------------------------------------------
| Authenticated + Verified Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // Generic dashboard — ResolveWorkspace middleware sends single-workspace
    // users straight to their dashboard, multi-workspace users to the
    // Workspace Selector (or their remembered choice). See
    // App\Services\Workspace\WorkspaceResolver for the role→workspace map.
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(ResolveWorkspace::class)
        ->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Workspace Selector (multi-role platform standard)
    |----------------------------------------------------------------------
    */
    Route::prefix('workspace')->name('workspace.')->group(function () {
        Route::get('/', [WorkspaceController::class, 'select'])->name('select');
        Route::post('/{workspace}', [WorkspaceController::class, 'enter'])->name('enter');
    });

    /*
    |----------------------------------------------------------------------
    | Profile Management
    |----------------------------------------------------------------------
    */
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/update', [ProfileController::class, 'updateProfile'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
        Route::post('/avatar', [ProfileController::class, 'uploadAvatar'])->name('avatar');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Admin Panel (super_admin, platform_admin, and other staff roles)
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('permission:system.view_health|users.manage_all|cms.manage|reviews.moderate|billing.manage_all|support.manage_all_tickets|advisor.manage_team')
        ->group(function () {

            Route::get('/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
                ->name('dashboard');

            Route::prefix('users')->name('users.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('index');
                Route::get('/{user}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('show');
                Route::put('/{user}/roles', [\App\Http\Controllers\Admin\UserController::class, 'updateRoles'])->name('roles.update');
                Route::delete('/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('destroy');
            });

            // Agency Applications Queue & Moderation (Phase 8)
            Route::prefix('agencies')->name('agencies.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\AgencyController::class, 'index'])->name('index');
                Route::get('/{agency}', [\App\Http\Controllers\Admin\AgencyController::class, 'show'])->name('show');
                Route::post('/{agency}/approve', [\App\Http\Controllers\Admin\AgencyController::class, 'approve'])->name('approve');
                Route::post('/{agency}/reject', [\App\Http\Controllers\Admin\AgencyController::class, 'reject'])->name('reject');
                Route::post('/{agency}/request-changes', [\App\Http\Controllers\Admin\AgencyController::class, 'requestChanges'])->name('request-changes');
                Route::post('/{agency}/suspend', [\App\Http\Controllers\Admin\AgencyController::class, 'suspend'])->name('suspend');
                Route::post('/{agency}/reactivate', [\App\Http\Controllers\Admin\AgencyController::class, 'reactivate'])->name('reactivate');
                Route::post('/{agency}/notes', [\App\Http\Controllers\Admin\AgencyController::class, 'storeNote'])->name('notes.store');
                Route::get('/{agency}/documents/{document}/download', [\App\Http\Controllers\Admin\AgencyController::class, 'downloadDocument'])->name('documents.download');
            });

            // Platform Settings (Phase 10) — Super Admin only, enforced by
            // the manage-platform-settings gate (super_admin, or a future
            // role explicitly granted system.manage_settings — currently
            // no role but super_admin holds it, per RoleSeeder).
            Route::prefix('settings')
                ->name('settings.')
                ->middleware('can:manage-platform-settings')
                ->group(function () {
                    Route::get('/', function () { return redirect()->route('admin.settings.general.edit'); })->name('index');

                    Route::get('/general', [\App\Http\Controllers\Admin\Settings\GeneralSettingsController::class, 'edit'])->name('general.edit');
                    Route::put('/general', [\App\Http\Controllers\Admin\Settings\GeneralSettingsController::class, 'update'])->name('general.update');

                    Route::get('/mail', [\App\Http\Controllers\Admin\Settings\MailSettingsController::class, 'edit'])->name('mail.edit');
                    Route::put('/mail', [\App\Http\Controllers\Admin\Settings\MailSettingsController::class, 'update'])->name('mail.update');
                    Route::post('/mail/test', [\App\Http\Controllers\Admin\Settings\MailSettingsController::class, 'sendTest'])->name('mail.test');

                    Route::get('/branding', [\App\Http\Controllers\Admin\Settings\BrandingSettingsController::class, 'edit'])->name('branding.edit');
                    Route::put('/branding', [\App\Http\Controllers\Admin\Settings\BrandingSettingsController::class, 'update'])->name('branding.update');

                    Route::get('/templates', [\App\Http\Controllers\Admin\Settings\EmailTemplateController::class, 'index'])->name('templates.index');
                    Route::get('/templates/{template}/edit', [\App\Http\Controllers\Admin\Settings\EmailTemplateController::class, 'edit'])->name('templates.edit');
                    Route::put('/templates/{template}', [\App\Http\Controllers\Admin\Settings\EmailTemplateController::class, 'update'])->name('templates.update');

                    Route::get('/email-logs', [\App\Http\Controllers\Admin\Settings\EmailLogController::class, 'index'])->name('email-logs.index');

                    Route::get('/failed-emails', [\App\Http\Controllers\Admin\Settings\FailedEmailController::class, 'index'])->name('failed-emails.index');
                    Route::post('/failed-emails/process-queue', [\App\Http\Controllers\Admin\Settings\FailedEmailController::class, 'processQueue'])->name('failed-emails.process-queue');
                    Route::post('/failed-emails/{uuid}/retry', [\App\Http\Controllers\Admin\Settings\FailedEmailController::class, 'retry'])->name('failed-emails.retry');
                    Route::delete('/failed-emails/{uuid}', [\App\Http\Controllers\Admin\Settings\FailedEmailController::class, 'destroy'])->name('failed-emails.destroy');

                    Route::get('/system', [\App\Http\Controllers\Admin\Settings\SystemController::class, 'show'])->name('system.show');

                    // Matching Engine weight configuration (Phase 12)
                    Route::get('/matching', [\App\Http\Controllers\Admin\Settings\MatchingWeightsController::class, 'edit'])->name('matching.edit');
                    Route::put('/matching', [\App\Http\Controllers\Admin\Settings\MatchingWeightsController::class, 'update'])->name('matching.update');
                });

            // Lead Management (Phase 11) — Super Admin / Platform Admin
            // oversight of all leads platform-wide, gated by leads.manage_all
            // rather than a route-level role check, since this permission
            // (not a role) is what the requirement specifies.
            Route::prefix('leads')->name('leads.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\LeadController::class, 'index'])->name('index');
                Route::get('/{lead}', [\App\Http\Controllers\Admin\LeadController::class, 'show'])->name('show');
                Route::post('/{lead}/assign', [\App\Http\Controllers\Admin\LeadController::class, 'assign'])->name('assign');
            });

            // Advisors oversight (Phase 12 dashboard polish pass)
            Route::prefix('advisors')->name('advisors.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\AdvisorController::class, 'index'])->name('index');
                Route::get('/{advisor}', [\App\Http\Controllers\Admin\AdvisorController::class, 'show'])->name('show');
            });

            // Families oversight (Phase 12 dashboard polish pass)
            Route::prefix('families')->name('families.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\FamilyController::class, 'index'])->name('index');
                Route::get('/{family}', [\App\Http\Controllers\Admin\FamilyController::class, 'show'])->name('show');
            });

            // Audit Logs (Phase 12 dashboard polish pass) — activity_log
            // has been written to since Phase 5; this is its first admin UI.
            Route::get('/activity-log', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('activity-log.index');

            // Reports & Analytics (Phase 12 dashboard polish pass)
            Route::get('/reports', [\App\Http\Controllers\Admin\ReportsController::class, 'index'])->name('reports.index');

            // Referral oversight (Phase 13)
            Route::prefix('referrals')->name('referrals.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\ReferralController::class, 'index'])->name('index');
                Route::get('/{referral}', [\App\Http\Controllers\Admin\ReferralController::class, 'show'])->name('show');
            });
        });

    /*
    |----------------------------------------------------------------------
    | Advisor CRM & Lead Management (Phase 11)
    |----------------------------------------------------------------------
    | advisor|advisor_manager ONLY — consistent with the same ownership-
    | boundary correction applied to Agency (Phase 8) and Family (Phase 9):
    | Super Admin / Platform Admin manage leads through the Admin Panel's
    | own Lead surface (admin.leads.*), not by operating the advisor-
    | facing CRM tools directly.
    |----------------------------------------------------------------------
    */
    Route::prefix('advisor')
        ->name('advisor.')
        ->middleware('role:advisor|advisor_manager')
        ->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Advisor\DashboardController::class, 'index'])->name('dashboard');

            Route::get('/profile', [\App\Http\Controllers\Advisor\ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [\App\Http\Controllers\Advisor\ProfileController::class, 'update'])->name('profile.update');
            Route::post('/profile/photo', [\App\Http\Controllers\Advisor\ProfileController::class, 'uploadPhoto'])->name('profile.photo.store');
            Route::delete('/profile/photo', [\App\Http\Controllers\Advisor\ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');

            Route::get('/territories', [\App\Http\Controllers\Advisor\TerritoryController::class, 'index'])->name('territories.index');
            Route::post('/territories', [\App\Http\Controllers\Advisor\TerritoryController::class, 'store'])->name('territories.store');
            Route::delete('/territories/{territory}', [\App\Http\Controllers\Advisor\TerritoryController::class, 'destroy'])->name('territories.destroy');

            Route::get('/calendar', [\App\Http\Controllers\Advisor\CalendarController::class, 'index'])->name('calendar');

            Route::get('/leads', [\App\Http\Controllers\Advisor\LeadController::class, 'index'])->name('leads.index');
            Route::post('/leads', [\App\Http\Controllers\Advisor\LeadController::class, 'store'])->name('leads.store');
            Route::get('/leads/{lead}', [\App\Http\Controllers\Advisor\LeadController::class, 'show'])->name('leads.show');
            Route::put('/leads/{lead}/status', [\App\Http\Controllers\Advisor\LeadController::class, 'updateStatus'])->name('leads.status');

            Route::get('/pipeline', [\App\Http\Controllers\Advisor\PipelineController::class, 'index'])->name('pipeline');
            Route::post('/leads/{lead}/move', [\App\Http\Controllers\Advisor\PipelineController::class, 'move'])->name('leads.move');

            Route::post('/leads/{lead}/notes', [\App\Http\Controllers\Advisor\LeadNoteController::class, 'store'])->name('leads.notes.store');

            Route::get('/tasks', [\App\Http\Controllers\Advisor\TaskController::class, 'index'])->name('tasks.index');
            Route::post('/tasks', [\App\Http\Controllers\Advisor\TaskController::class, 'store'])->name('tasks.store');
            Route::get('/tasks/{task}/edit', [\App\Http\Controllers\Advisor\TaskController::class, 'edit'])->name('tasks.edit');
            Route::put('/tasks/{task}', [\App\Http\Controllers\Advisor\TaskController::class, 'update'])->name('tasks.update');
            Route::post('/tasks/{task}/complete', [\App\Http\Controllers\Advisor\TaskController::class, 'complete'])->name('tasks.complete');
            Route::post('/tasks/{task}/reopen', [\App\Http\Controllers\Advisor\TaskController::class, 'reopen'])->name('tasks.reopen');
            Route::delete('/tasks/{task}', [\App\Http\Controllers\Advisor\TaskController::class, 'destroy'])->name('tasks.destroy');

            Route::get('/tours', [\App\Http\Controllers\Advisor\TourController::class, 'index'])->name('tours.index');
            Route::post('/leads/{lead}/tours', [\App\Http\Controllers\Advisor\TourController::class, 'store'])->name('leads.tours.store');
            Route::put('/tours/{tourRequest}', [\App\Http\Controllers\Advisor\TourController::class, 'update'])->name('tours.update');
            Route::put('/tours/{tourRequest}/status', [\App\Http\Controllers\Advisor\TourController::class, 'updateStatus'])->name('tours.status');
            Route::delete('/tours/{tourRequest}', [\App\Http\Controllers\Advisor\TourController::class, 'destroy'])->name('tours.destroy');

            Route::get('/leads/{lead}/documents/{document}/download', [\App\Http\Controllers\Advisor\DocumentController::class, 'download'])->name('leads.documents.download');

            // Recommendations (Phase 12 — Intelligent Matching Engine)
            Route::get('/leads/{lead}/recommendations', [\App\Http\Controllers\Advisor\RecommendationController::class, 'index'])->name('leads.recommendations.index');
            Route::post('/leads/{lead}/recommendations/generate', [\App\Http\Controllers\Advisor\RecommendationController::class, 'generate'])->name('leads.recommendations.generate');
            Route::post('/leads/{lead}/recommendations/reorder', [\App\Http\Controllers\Advisor\RecommendationController::class, 'reorder'])->name('leads.recommendations.reorder');
            Route::post('/leads/{lead}/recommendations/{matchResult}/approve', [\App\Http\Controllers\Advisor\RecommendationController::class, 'approve'])->name('leads.recommendations.approve');
            Route::post('/leads/{lead}/recommendations/{matchResult}/hide', [\App\Http\Controllers\Advisor\RecommendationController::class, 'hide'])->name('leads.recommendations.hide');

            // Referral & Tour Management (Phase 13)
            Route::post('/leads/{lead}/referrals', [\App\Http\Controllers\Advisor\ReferralController::class, 'store'])->name('leads.referrals.store');
            Route::get('/referrals', [\App\Http\Controllers\Advisor\ReferralController::class, 'index'])->name('referrals.index');
            Route::get('/referrals/{referral}', [\App\Http\Controllers\Advisor\ReferralController::class, 'show'])->name('referrals.show');
            Route::put('/referrals/{referral}/priority', [\App\Http\Controllers\Advisor\ReferralController::class, 'updatePriority'])->name('referrals.priority');
            Route::put('/referrals/{referral}/status', [\App\Http\Controllers\Advisor\ReferralController::class, 'transition'])->name('referrals.transition');
            Route::post('/referrals/{referral}/cancel', [\App\Http\Controllers\Advisor\ReferralController::class, 'cancel'])->name('referrals.cancel');
            Route::post('/referrals/{referral}/notes', [\App\Http\Controllers\Advisor\ReferralController::class, 'addNote'])->name('referrals.notes.store');
            Route::post('/referrals/{referral}/tours', [\App\Http\Controllers\Advisor\ReferralController::class, 'scheduleTour'])->name('referrals.tours.store');

            Route::get('/leads/{lead}/conversation', [\App\Http\Controllers\Advisor\ConversationController::class, 'show'])->name('leads.conversation');
            Route::post('/leads/{lead}/conversation', [\App\Http\Controllers\Advisor\ConversationController::class, 'store'])->name('leads.conversation.store');

            Route::get('/notifications', [\App\Http\Controllers\Advisor\NotificationController::class, 'index'])->name('notifications.index');
            Route::post('/notifications/{notificationId}/read', [\App\Http\Controllers\Advisor\NotificationController::class, 'markAsRead'])->name('notifications.read');
            Route::post('/notifications/read-all', [\App\Http\Controllers\Advisor\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
            Route::get('/notification-preferences', [\App\Http\Controllers\Advisor\NotificationPreferenceController::class, 'edit'])->name('notification-preferences.edit');
            Route::put('/notification-preferences', [\App\Http\Controllers\Advisor\NotificationPreferenceController::class, 'update'])->name('notification-preferences.update');

            Route::get('/inbox', [\App\Http\Controllers\Advisor\InboxController::class, 'index'])->name('inbox');
            Route::get('/notes', [\App\Http\Controllers\Advisor\NoteController::class, 'index'])->name('notes.index');
            Route::get('/activity', [\App\Http\Controllers\Advisor\ActivityController::class, 'index'])->name('activity');
        });

    /*
    |----------------------------------------------------------------------
    | Family & Care Seeker Module (Phase 9)
    |----------------------------------------------------------------------
    | family|reviewer ONLY — per explicit Phase 9 instruction ("Administrators
    | retain moderation access only through the Admin Panel"), super_admin/
    | platform_admin are deliberately excluded here, consistent with the
    | same ownership-boundary correction applied to the Agency module in
    | Phase 8 (an admin has no legitimate reason to personally operate a
    | family's own data — admin oversight, if ever needed, belongs in the
    | Admin Panel, not these owner-facing routes).
    |----------------------------------------------------------------------
    */
    Route::prefix('family')
        ->name('family.')
        ->middleware(['role:family|reviewer', \App\Http\Middleware\EnsureFamilyRecordExists::class])
        ->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Family\DashboardController::class, 'index'])->name('dashboard');

            // Family Profile
            Route::get('/profile', [\App\Http\Controllers\Family\ProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [\App\Http\Controllers\Family\ProfileController::class, 'update'])->name('profile.update');

            // Care Seekers
            Route::prefix('care-seekers')->name('care-seekers.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Family\CareSeekerController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Family\CareSeekerController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Family\CareSeekerController::class, 'store'])->name('store');
                Route::get('/{care_seeker}/edit', [\App\Http\Controllers\Family\CareSeekerController::class, 'edit'])->name('edit');
                Route::put('/{care_seeker}', [\App\Http\Controllers\Family\CareSeekerController::class, 'update'])->name('update');
                Route::delete('/{care_seeker}', [\App\Http\Controllers\Family\CareSeekerController::class, 'destroy'])->name('destroy');
                Route::post('/{care_seeker}/photo', [\App\Http\Controllers\Family\CareSeekerController::class, 'uploadPhoto'])->name('photo.store');
                Route::delete('/{care_seeker}/photo', [\App\Http\Controllers\Family\CareSeekerController::class, 'destroyPhoto'])->name('photo.destroy');

                // Documents
                Route::get('/{care_seeker}/documents', [\App\Http\Controllers\Family\CareSeekerDocumentController::class, 'index'])->name('documents.index');
                Route::post('/{care_seeker}/documents', [\App\Http\Controllers\Family\CareSeekerDocumentController::class, 'store'])->name('documents.store');
                Route::get('/{care_seeker}/documents/{document}/download', [\App\Http\Controllers\Family\CareSeekerDocumentController::class, 'download'])->name('documents.download');
                Route::delete('/{care_seeker}/documents/{document}', [\App\Http\Controllers\Family\CareSeekerDocumentController::class, 'destroy'])->name('documents.destroy');

                // Recommendations (Phase 12 — Intelligent Matching Engine)
                Route::get('/{care_seeker}/recommendations', [\App\Http\Controllers\Family\RecommendationController::class, 'index'])->name('recommendations.index');
                Route::post('/{care_seeker}/recommendations/regenerate', [\App\Http\Controllers\Family\RecommendationController::class, 'regenerate'])->name('recommendations.regenerate');
                Route::post('/{care_seeker}/recommendations/{matchResult}/shortlist', [\App\Http\Controllers\Family\RecommendationController::class, 'toggleShortlist'])->name('recommendations.shortlist');
            });

            // Needs Assessment
            Route::prefix('needs-assessment')->name('needs-assessment.')->group(function () {
                Route::get('/drafts', [\App\Http\Controllers\Family\NeedsAssessmentController::class, 'drafts'])->name('drafts');
                Route::get('/{care_seeker}/start', [\App\Http\Controllers\Family\NeedsAssessmentController::class, 'start'])->name('start');
                Route::get('/{care_seeker}/step/{step}', [\App\Http\Controllers\Family\NeedsAssessmentController::class, 'step'])->name('step');
                Route::post('/{care_seeker}/step/{step}', [\App\Http\Controllers\Family\NeedsAssessmentController::class, 'storeStep'])->name('step.store');
                Route::post('/{care_seeker}/complete', [\App\Http\Controllers\Family\NeedsAssessmentController::class, 'complete'])->name('complete');
            });

            // Favorites
            Route::get('/favorites', [\App\Http\Controllers\Family\FavoriteController::class, 'index'])->name('favorites.index');
            Route::get('/shortlist', [\App\Http\Controllers\Family\ShortlistController::class, 'index'])->name('shortlist.index');

            // Referral & Tour tracking (Phase 13) — read-only + tour
            // cancellation only; families never contact agencies directly.
            Route::get('/referrals', [\App\Http\Controllers\Family\ReferralController::class, 'index'])->name('referrals.index');
            Route::get('/referrals/{referral}', [\App\Http\Controllers\Family\ReferralController::class, 'show'])->name('referrals.show');
            Route::get('/tours', [\App\Http\Controllers\Family\TourController::class, 'index'])->name('tours.index');
            Route::post('/tours/{tourRequest}/cancel', [\App\Http\Controllers\Family\TourController::class, 'cancel'])->name('tours.cancel');

            // Advisor messaging (Phase 13) — the family-side counterpart
            // to advisor.leads.conversation, which had no family-facing
            // reply capability until now.
            Route::get('/leads/{lead}/conversation', [\App\Http\Controllers\Family\ConversationController::class, 'show'])->name('leads.conversation');
            Route::post('/leads/{lead}/conversation', [\App\Http\Controllers\Family\ConversationController::class, 'store'])->name('leads.conversation.store');
            Route::post('/favorites/{agency}/toggle', [\App\Http\Controllers\Family\FavoriteController::class, 'toggle'])->name('favorites.toggle');

            // Compare
            Route::get('/compare', [\App\Http\Controllers\Family\CompareController::class, 'show'])->name('compare');

            // Notes
            Route::get('/notes', [\App\Http\Controllers\Family\NoteController::class, 'index'])->name('notes.index');
            Route::post('/notes', [\App\Http\Controllers\Family\NoteController::class, 'store'])->name('notes.store');
            Route::delete('/notes/{note}', [\App\Http\Controllers\Family\NoteController::class, 'destroy'])->name('notes.destroy');

            // Notifications
            Route::get('/notifications', [\App\Http\Controllers\Family\NotificationController::class, 'index'])->name('notifications.index');
            Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Family\NotificationController::class, 'markAsRead'])->name('notifications.read');
            Route::post('/notifications/read-all', [\App\Http\Controllers\Family\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

            // Activity Timeline
            Route::get('/activity', [\App\Http\Controllers\Family\ActivityController::class, 'index'])->name('activity');
        });

    /*
    |----------------------------------------------------------------------
    | Affiliate
    |----------------------------------------------------------------------
    */
    Route::prefix('affiliate')
        ->name('affiliate.')
        ->middleware('role:affiliate|super_admin|platform_admin')
        ->group(function () {
            // NOTE: route name is 'dashboard' (not 'affiliate.dashboard') so
            // combined with the group's 'affiliate.' prefix it correctly
            // resolves to 'affiliate.dashboard' — a prior version double-
            // prefixed this to 'affiliate.affiliate.dashboard'.
            Route::get('/dashboard', [\App\Http\Controllers\Affiliate\DashboardController::class, 'index'])->name('dashboard');
        });

    /*
    |----------------------------------------------------------------------
    | Agency Onboarding Wizard (5 steps)
    |----------------------------------------------------------------------
    | agency_owner ONLY. Super Admin / Platform Admin must never create or
    | own an Agency record through this owner-facing wizard — agency
    | oversight (approve/reject/suspend) belongs to the Admin Panel
    | (Phase 8), not this flow. See DATABASE_DECISIONS.md for the
    | architecture note on this change.
    |----------------------------------------------------------------------
    */
    Route::prefix('register-agency')
        ->name('agency.register.')
        ->middleware('role:agency_owner')
        ->group(function () {
            Route::get('/', [AgencyRegistrationController::class, 'start'])->name('start');

            Route::get('/step-1', [AgencyRegistrationController::class, 'step1'])->name('step1');
            Route::post('/step-1', [AgencyRegistrationController::class, 'storeStep1'])->name('step1.store');

            Route::get('/step-2', [AgencyRegistrationController::class, 'step2'])->name('step2');
            Route::post('/step-2', [AgencyRegistrationController::class, 'storeStep2'])->name('step2.store');

            Route::get('/step-3', [AgencyRegistrationController::class, 'step3'])->name('step3');
            Route::post('/step-3', [AgencyRegistrationController::class, 'storeStep3'])->name('step3.store');

            Route::get('/step-4', [AgencyRegistrationController::class, 'step4'])->name('step4');
            Route::post('/step-4', [AgencyRegistrationController::class, 'storeStep4'])->name('step4.store');

            Route::get('/step-5', [AgencyRegistrationController::class, 'step5'])->name('step5');
            Route::post('/complete', [AgencyRegistrationController::class, 'complete'])->name('complete');
        });

    /*
    |----------------------------------------------------------------------
    | Agency Dashboard (Agency Owner + Agency Staff)
    |----------------------------------------------------------------------
    | Super Admin / Platform Admin removed — see note above the wizard
    | group. agency_staff retained (unchanged from Phase 7, tested):
    | staff operate within an owner's existing agency, they don't create
    | or own one, so their access here doesn't conflict with the
    | ownership rule this change is protecting.
    |----------------------------------------------------------------------
    */
    Route::prefix('agency')
        ->name('agency.')
        ->middleware('role:agency_owner|agency_staff')
        ->group(function () {

            Route::get('/dashboard', [AgencyDashboardController::class, 'dashboard'])->name('dashboard');

            // Profile
            Route::get('/profile', [AgencyProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile', [AgencyProfileController::class, 'update'])->name('profile.update');

            // Services
            Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
            Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
            Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
            Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

            // Pricing
            Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.index');
            Route::post('/pricing', [PricingController::class, 'store'])->name('pricing.store');
            Route::put('/pricing/{pricing}', [PricingController::class, 'update'])->name('pricing.update');
            Route::delete('/pricing/{pricing}', [PricingController::class, 'destroy'])->name('pricing.destroy');

            // Staff
            Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
            Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
            Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');

            // Notifications (Phase 12 dashboard polish pass) — reuses the
            // same shared trait as Family/Advisor notification centers.
            Route::get('/notifications', [\App\Http\Controllers\Agency\NotificationController::class, 'index'])->name('notifications.index');
            Route::post('/notifications/{notificationId}/read', [\App\Http\Controllers\Agency\NotificationController::class, 'markAsRead'])->name('notifications.read');
            Route::post('/notifications/read-all', [\App\Http\Controllers\Agency\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

            // Subscription (Phase 12 dashboard polish pass)
            Route::get('/subscription', [\App\Http\Controllers\Agency\SubscriptionController::class, 'index'])->name('subscription.index');

            // Analytics (Phase 12 dashboard polish pass) — deliberately
            // aggregate-only, see AnalyticsController's docblock for why
            // this never exposes matching scores.
            Route::get('/analytics', [\App\Http\Controllers\Agency\AnalyticsController::class, 'index'])->name('analytics.index');

            // Business Hours
            Route::get('/hours', [HourController::class, 'index'])->name('hours.index');
            Route::put('/hours', [HourController::class, 'update'])->name('hours.update');

            // Coverage Areas
            Route::get('/coverage', [CoverageController::class, 'index'])->name('coverage.index');
            Route::post('/coverage', [CoverageController::class, 'store'])->name('coverage.store');
            Route::delete('/coverage/{coverage}', [CoverageController::class, 'destroy'])->name('coverage.destroy');

            // Certifications
            Route::get('/certifications', [CertificationController::class, 'index'])->name('certifications.index');
            Route::post('/certifications', [CertificationController::class, 'store'])->name('certifications.store');
            Route::delete('/certifications/{certification}', [CertificationController::class, 'destroy'])->name('certifications.destroy');

            // Insurance / License Documents (private, signed download)
            Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
            Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
            Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
            Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

            // Media Gallery
            Route::get('/media', [MediaController::class, 'index'])->name('media.index');
            Route::post('/media', [MediaController::class, 'store'])->name('media.store');
            Route::post('/media/reorder', [MediaController::class, 'reorder'])->name('media.reorder');
            Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

            // Referral Inbox (Phase 13) — supersedes the placeholder Lead
            // Inbox link (Phase 7), which queried the same referrals
            // table but never had real data to show until now.
            Route::get('/leads', [LeadInboxController::class, 'index'])->name('leads.index');
            Route::get('/referrals', [\App\Http\Controllers\Agency\ReferralController::class, 'index'])->name('referrals.index');
            Route::get('/referrals/{referral}', [\App\Http\Controllers\Agency\ReferralController::class, 'show'])->name('referrals.show');
            Route::post('/referrals/{referral}/accept', [\App\Http\Controllers\Agency\ReferralController::class, 'accept'])->name('referrals.accept');
            Route::post('/referrals/{referral}/decline', [\App\Http\Controllers\Agency\ReferralController::class, 'decline'])->name('referrals.decline');
            Route::post('/referrals/{referral}/request-info', [\App\Http\Controllers\Agency\ReferralController::class, 'requestMoreInfo'])->name('referrals.request-info');
            Route::post('/referrals/{referral}/notes', [\App\Http\Controllers\Agency\ReferralController::class, 'addNote'])->name('referrals.notes.store');

            // Tour Management (Phase 13)
            Route::get('/tours', [\App\Http\Controllers\Agency\TourController::class, 'index'])->name('tours.index');
            Route::post('/referrals/{referral}/tours', [\App\Http\Controllers\Agency\TourController::class, 'store'])->name('referrals.tours.store');
            Route::put('/tours/{tourRequest}/reschedule', [\App\Http\Controllers\Agency\TourController::class, 'reschedule'])->name('tours.reschedule');
            Route::post('/tours/{tourRequest}/confirm', [\App\Http\Controllers\Agency\TourController::class, 'confirm'])->name('tours.confirm');
            Route::post('/tours/{tourRequest}/complete', [\App\Http\Controllers\Agency\TourController::class, 'complete'])->name('tours.complete');

            // Settings (plan, featured listing)
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('/settings/featured', [SettingsController::class, 'toggleFeatured'])->name('settings.featured');
        });
});

require __DIR__.'/auth.php';
