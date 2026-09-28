<?php

namespace App\Providers;

use App\Listeners\LogFailedEmailJob;
use App\Listeners\LogSentEmail;
use App\Models\Agency;
use App\Models\CareSeeker;
use App\Models\Family;
use App\Models\Referral;
use App\Models\Review;
use App\Models\User;
use App\Policies\AgencyPolicy;
use App\Policies\CareSeekerPolicy;
use App\Policies\FamilyPolicy;
use App\Policies\ReferralPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\UserPolicy;
use App\Services\Settings\MailConfigService;
use App\Services\Workspace\WorkspaceResolver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    protected array $policies = [
        User::class              => UserPolicy::class,
        Agency::class            => AgencyPolicy::class,
        Family::class            => FamilyPolicy::class,
        \App\Models\CmsPage::class      => \App\Policies\CmsPagePolicy::class,
        \App\Models\BlogPost::class     => \App\Policies\BlogPostPolicy::class,
        CareSeeker::class        => CareSeekerPolicy::class,
        Referral::class          => ReferralPolicy::class,
        Review::class            => ReviewPolicy::class,
        \App\Models\FamilyNote::class         => \App\Policies\FamilyNotePolicy::class,
        \App\Models\CareSeekerDocument::class => \App\Policies\CareSeekerDocumentPolicy::class,
        \App\Models\Lead::class               => \App\Policies\LeadPolicy::class,
        \App\Models\Advisor::class            => \App\Policies\AdvisorPolicy::class,
        \App\Models\AdvisorTask::class         => \App\Policies\AdvisorTaskPolicy::class,
        \App\Models\TourRequest::class         => \App\Policies\TourRequestPolicy::class,
        \App\Models\AdvisorTerritory::class    => \App\Policies\AdvisorTerritoryPolicy::class,
        \App\Models\Subscription::class        => \App\Policies\SubscriptionPolicy::class,
        \App\Models\Invoice::class              => \App\Policies\InvoicePolicy::class,
        \App\Models\Refund::class               => \App\Policies\RefundPolicy::class,
        \App\Models\Coupon::class               => \App\Policies\CouponPolicy::class,
    ];

    public function register(): void
    {
        // Load helpers before autoload regeneration is possible in this environment.
        require_once base_path('app/Helpers/helpers.php');
    }

    public function boot(): void
    {
        // ─── Register Policies ────────────────────────────────────────────────
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // ─── Platform-level Gates ─────────────────────────────────────────────
        // These cover actions that don't map to a single Eloquent model.

        /** Access the admin panel at all. */
        Gate::define('view-admin-panel', function (User $user): bool {
            return $user->hasAnyRole([
                'super_admin', 'platform_admin', 'advisor_manager',
                'billing_manager', 'support_agent', 'content_editor',
                'moderator', 'accountant',
            ]);
        });

        /** Manage platform-wide settings and configuration. */
        Gate::define('manage-platform-settings', function (User $user): bool {
            return $user->hasRole('super_admin') || $user->can('system.manage_settings');
        });

        /** View system health, queues, and logs in the admin panel. */
        Gate::define('view-system-health', function (User $user): bool {
            return $user->can('system.view_health');
        });

        /** Access the advisor CRM dashboard. */
        Gate::define('access-advisor-crm', function (User $user): bool {
            return $user->hasAnyRole(['advisor', 'advisor_manager', 'super_admin', 'platform_admin']);
        });

        /** Impersonate another user (Super Admin only, always logged). */
        Gate::define('impersonate-users', function (User $user): bool {
            return $user->hasRole('super_admin');
        });

        // ─── Rate Limiters ────────────────────────────────────────────────────
        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(5)
                ->by(strtolower($request->input('email')) . '|' . $request->ip());
        });

        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // ─── Dynamic Mail Configuration (Phase 10) ─────────────────────────────
        // Applies DB-stored SMTP settings to the runtime mail config so an
        // admin's changes in the Settings UI take effect immediately.
        // Guarded against a fresh install (settings table not migrated
        // yet) and the testing environment (tests should use their own
        // configured mail driver, unaffected by whatever an admin saved
        // in a previous manual testing session against the same DB).
        if (!$this->app->runningUnitTests() && Schema::hasTable('settings')) {
            $this->app->make(MailConfigService::class)->apply();
        }

        // ─── Email Logging (Phase 10) ───────────────────────────────────────────
        // Logs every outgoing email via Laravel's own mail/queue events
        // rather than instrumenting each send call site — see each
        // listener's docblock.
        Event::listen(MessageSent::class, LogSentEmail::class);
        Event::listen(JobFailed::class, LogFailedEmailJob::class);

        // ─── Workspace Switcher (multi-role platform standard) ────────────────
        // Shares $availableWorkspaces with every authenticated layout so the
        // "Switch Workspace" navbar link only renders for genuinely
        // multi-workspace users (see WorkspaceResolver / ResolveWorkspace).
        View::composer(
            ['layouts.app', 'layouts.agency', 'layouts.admin', 'layouts.family', 'layouts.advisor'],
            function ($view) {
                $user = auth()->user();
                $view->with(
                    'availableWorkspaces',
                    $user ? app(WorkspaceResolver::class)->availableWorkspaces($user) : []
                );
            }
        );
    }
}
