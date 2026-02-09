<?php

declare(strict_types=1);

namespace App\Providers;

use Hanafalah\MicroTenant\Contracts\Supports\ConnectionManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Stancl\JobPipeline\JobPipeline;
use Stancl\Tenancy\Events;
use Stancl\Tenancy\Jobs;
use Stancl\Tenancy\Listeners;
use Stancl\Tenancy\Middleware;
use Hanafalah\MicroTenant\Facades\MicroTenant;
use Hanafalah\MicroTenant\Listeners\BootstrapTenancy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Controllers\TenantAssetsController;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;

class MicroTenantServiceProvider extends ServiceProvider
{

    public static string $controllerNamespace = '';

    public function events()
    {
        return [
            // Tenant events
            Events\CreatingTenant::class => [],
            Events\TenantCreated::class => [
                JobPipeline::make([
                    Jobs\CreateDatabase::class,
                    // Jobs\MigrateDatabase::class,
                    // Jobs\SeedDatabase::class,

                    // Your own jobs to prepare the tenant.
                    // Provision API keys, create S3 buckets, anything you want!

                ])->send(function (Events\TenantCreated $event) {
                    return $event->tenant;
                })->shouldBeQueued(false), // `false` by default, but you probably want to make this `true` for production.
            ],
            Events\SavingTenant::class => [],
            Events\TenantSaved::class => [],
            Events\UpdatingTenant::class => [],
            Events\TenantUpdated::class => [],
            Events\DeletingTenant::class => [],
            Events\TenantDeleted::class => [
                JobPipeline::make([
                    Jobs\DeleteDatabase::class,
                ])->send(function (Events\TenantDeleted $event) {
                    return $event->tenant;
                })->shouldBeQueued(false), // `false` by default, but you probably want to make this `true` for production.
            ],

            // Domain events
            Events\CreatingDomain::class => [],
            Events\DomainCreated::class => [],
            Events\SavingDomain::class => [],
            Events\DomainSaved::class => [],
            Events\UpdatingDomain::class => [],
            Events\DomainUpdated::class => [],
            Events\DeletingDomain::class => [],
            Events\DomainDeleted::class => [],

            // Database events
            Events\DatabaseCreated::class => [],
            Events\DatabaseMigrated::class => [],
            Events\DatabaseSeeded::class => [],
            Events\DatabaseRolledBack::class => [],
            Events\DatabaseDeleted::class => [],

            // Tenancy events
            Events\InitializingTenancy::class => [],
            Events\TenancyInitialized::class => [
                Listeners\BootstrapTenancy::class         
            ],

            Events\EndingTenancy::class => [],
            Events\TenancyEnded::class => [
                Listeners\RevertToCentralContext::class,
            ],
            Events\BootstrappingTenancy::class => [],
            Events\TenancyBootstrapped::class => [
                function(){
                    $tenant = tenancy()->tenant;
                    app(config('micro-tenant.database.connection_manager'))->handle($tenant);

                    $base = $tenant->getKey().'/assets/';
                    config([
                        'filesystems.asset_url' => $base
                    ]);

                    $workspace = app(config('database.models.Workspace'));
                    $workspace = $tenant->reference;
                    if (isset($workspace)){
                        $integration = $workspace->integration;
                        if (!isset($integration)) {
                            if (method_exists($workspace,'getIntegrationPayload')){
                                $integration = $workspace->getIntegrationPayload();
                            }
                        }
                        if (isset($integration)){
                            if (isset($integration['satu_sehat']) && config('satu-sehat') !== null){
                                $ihs_number = $integration['satu_sehat']['general']['ihs_number'];
                                $client_id = $integration['satu_sehat']['general']['client_id'] ?? config('satu-sehat.client_id',env('SS_CLIENT_ID'));
                                $client_secret = $integration['satu_sehat']['general']['client_id'] ?? config('satu-sehat.client_secret',env('SS_CLIENT_SECRET'));
                                $organization_id = $integration['satu_sehat']['general']['client_id'] ?? config('satu-sehat.organization_id',env('SS_ORGANIZATION_ID'));
                                config([
                                    'satu-sehat.client_organization_id' => $ihs_number ?? $organization_id,
                                    'satu-sehat.client_id' => $client_id,
                                    'satu-sehat.client_secret' => $client_secret
                                ]);
                            }
                        }
                    }
                    config([
                        'app.workspace_model' => $workspace
                    ]);

                    // Temporarily removed timezone setting to debug memory issue
                    // $this->setWorkspaceTimezone($tenant, $workspace);

                    // Set dynamic Elasticsearch prefix based on tenant
                    if (config('elasticsearch.enabled', false)) {
                        // Extract tenant ID from session, request header, or env
                        $tenantId = tenancy()->tenant->id;

                        if ($tenantId) {
                            config(['elasticsearch.prefix' => env('APP_ENV', 'development').'.'.$tenantId]);
                        }
                    }
                }
            ],
            Events\RevertingToCentralContext::class => [],
            Events\RevertedToCentralContext::class => [],

            // Resource syncing
            Events\SyncedResourceSaved::class => [
                Listeners\UpdateSyncedResource::class,
            ],

            // Fired only when a synced resource is changed in a different DB than the origin DB (to avoid infinite loops)
            Events\SyncedResourceChangedInForeignDatabase::class => [],
        ];
    }

    public function register()
    {
    }

    public function boot()
    {
        $this->bootEvents();
        $this->mapRoutes();
        $this->makeTenancyMiddlewareHighestPriority();
        TenantAssetsController::$tenancyMiddleware = InitializeTenancyByPath::class;
    }

    protected function mapRoutes()
    {
            if (file_exists(base_path('routes/tenant.php'))) {
                Route::namespace(static::$controllerNamespace)
                    ->group(base_path('routes/tenant.php'));
            }
    }

    protected function bootEvents()
    {
        foreach ($this->events() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof JobPipeline) {
                    $listener = $listener->toListener();
                }

                Event::listen($event, $listener);
            }
        }
    }

    protected function makeTenancyMiddlewareHighestPriority()
    {
        $tenancyMiddleware = [
            // Even higher priority than the initialization middleware
            Middleware\PreventAccessFromCentralDomains::class,

            Middleware\InitializeTenancyByDomain::class,
            Middleware\InitializeTenancyBySubdomain::class,
            Middleware\InitializeTenancyByDomainOrSubdomain::class,
            Middleware\InitializeTenancyByPath::class,
            Middleware\InitializeTenancyByRequestData::class,
        ];

        foreach (array_reverse($tenancyMiddleware) as $middleware) {
            $this->app[\Illuminate\Contracts\Http\Kernel::class]->prependToMiddlewarePriority($middleware);
        }
    }

    /**
     * Set workspace timezone for the current request.
     *
     * This method is Octane-safe as it sets config per-request without static variables.
     * The timezone is read from workspace settings: $workspace->setting['timezone']['name']
     *
     * Uses raw attribute access to avoid triggering accessors/casts that could cause
     * circular dependencies during boot phase.
     *
     * @param  mixed  $tenant
     * @param  mixed  $workspace
     * @return void
     */
    protected function setWorkspaceTimezone($tenant, $workspace): void
    {
        try {
            if ($tenant->reference_type != $workspace->getMorphClass()) {
                return;
            }

            $timezone = null;

            // Use getAttributes() to get raw data without triggering accessors/casts
            // This prevents circular dependency issues during boot
            if (method_exists($workspace, 'getAttributes')) {
                $attributes = $workspace->getAttributes();

                if (isset($attributes['setting'])) {
                    $setting = $attributes['setting'];

                    // If setting is JSON string, decode it
                    if (is_string($setting)) {
                        $setting = json_decode($setting, true);
                    }

                    // Try to get timezone from workspace settings
                    // Expected structure: $workspace->setting['timezone']['name'] = 'Asia/Jakarta'
                    if (is_array($setting) &&
                        isset($setting['timezone_id']) &&
                        isset($setting['timezone']['name'])) {
                        $timezone = $setting['timezone']['name'];
                    }
                }
            }

            // Validate timezone
            if ($timezone && !in_array($timezone, timezone_identifiers_list())) {
                // Invalid timezone, use fallback
                $timezone = null;
            }

            // Fallback to app timezone if not set or invalid
            $timezone = $timezone ?? config('app.timezone', 'UTC');

            // Set the workspace timezone for this request
            config()->set('app.client_timezone', $timezone);
        } catch (\Throwable $e) {
            // If anything goes wrong, set default timezone
            // Log the error if debug mode is enabled
            if (\config('app.debug')) {
                Log::warning('Failed to set workspace timezone: ' . $e->getMessage());
            }

            \config()->set('app.client_timezone', \config('app.timezone', 'UTC'));
        }
    }
}
