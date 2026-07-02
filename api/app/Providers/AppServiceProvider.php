<?php

namespace App\Providers;

use App\Models\Decision;
use App\Models\Document;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Request;
use App\Models\RequestHistoryEntry;
use App\Models\UserAccount;
use App\Policies\DecisionPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\MessagePolicy;
use App\Policies\NotificationPolicy;
use App\Policies\RequestHistoryEntryPolicy;
use App\Policies\RequestPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerRoleGates();
    }

    /**
     * Map models to their request-scoped policies (§5.5).
     */
    private function registerPolicies(): void
    {
        Gate::policy(Request::class, RequestPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(Message::class, MessagePolicy::class);
        Gate::policy(Decision::class, DecisionPolicy::class);
        Gate::policy(RequestHistoryEntry::class, RequestHistoryEntryPolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);
    }

    /**
     * Role gates from Table BR-016. Each fails closed: an inactive account, or a
     * role outside the rule, is denied [05_system-design.md §4].
     */
    private function registerRoleGates(): void
    {
        Gate::define('assign-requests', fn (UserAccount $user) => $user->isActive() && $user->isAdministrator());   // [BR-010]
        Gate::define('manage-categories', fn (UserAccount $user) => $user->isActive() && $user->isAdministrator()); // [BR-012]
        Gate::define('manage-accounts', fn (UserAccount $user) => $user->isActive() && $user->isAdministrator());   // [BR-013]
        Gate::define('manage-settings', fn (UserAccount $user) => $user->isActive() && $user->isAdministrator());   // [BR-014]
        Gate::define('view-reporting', fn (UserAccount $user) => $user->isActive()
            && ($user->isStaffMember() || $user->isAdministrator()));                                               // [BR-015]
    }
}
