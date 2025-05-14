<?php

use Hanafalah\MicroTenant\Models as MicroTenantModels;
use Hanafalah\MicroTenant\Commands as Commands;

return [
    'enabled'      => true,
    'dev_mode'     => true,
    'login_schema' => null,
    'application'  => [
        /**
         * pattern for versioning, you can use 1.^, 1.0.^, 1.0.0, 
         * but avoid using 1.0.0 because it will make schema installation become not optimal
         */
        'version_pattern' => '1.^'
    ],
    'libs' => [
        'model' => 'Models',
        'contract' => 'Contracts',
        'schema' => 'Schemas',
        'database' => 'Database'
    ],
    'database' => [
        'central_tenant'   => [
            'prefix' => 'central_tenant_',
            'suffix' => ''
        ],
        'scope'     => [
            'paths' => [
                'App/Scopes'
            ]
        ],
        'models'  => [
            //
        ],
        'model_connections' => [
            "central"        => [],
            "central_app"    => [],
            "central_tenant" => []
        ],
        'connections' => [
            //THIS SETUP DEFAULT FOR MYSQL
            'central_connection' => [
                'driver'         => env('DB_DRIVER', 'pgsql'),
                'url'            => env('DB_URL'),
                'host'           => env('DB_HOST', '127.0.0.1'),
                'port'           => env('DB_PORT', '3306'),
                'database'       => env('DB_DATABASE', 'central'),
                'username'       => env('DB_USERNAME', 'root'),
                'password'       => env('DB_PASSWORD', ''),
                'charset'        => env('DB_CHARSET', 'utf8'),
                'prefix'         => '',
                'prefix_indexes' => true,
                'search_path'    => 'public',
                'sslmode'        => 'prefer',
            ],

            /**
             * Connection used as a "template" for the dynamically created tenant database connection.
             * Note: don't name your template connection tenant. That name is reserved by package.
             */
            'template_tenant_connection' => null,

        ],
        'database_tenant_name' => [
            'prefix' => 'microtenant_',
            'suffix' => ''
        ],

        /**
         * TenantDatabaseManagers are classes that handle the creation & deletion of tenant databases.
         */
        'managers' => [
            // 'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            // 'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            // 'pgsql'  => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,

            /**
             * Use this database manager for MySQL to have a DB user created for each tenant database.
             * You can customize the grants given to these users by changing the $grants property.
             */
            // 'mysql' => Stancl\Tenancy\TenantDatabaseManagers\PermissionControlledMySQLDatabaseManager::class,

            /**
         * Disable the pgsql manager above, and enable the one below if you
         * want to separate tenant DBs by schemas rather than databases.
         */
            'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLSchemaManager::class, // Separate by schema instead of database
        ]
    ],
    'domains'  => [
        /**
         * Only relevant if you're using the domain or subdomain identification middleware.
         */
        'central_domains' => [
            '127.0.0.1',
            'localhost',
        ],
        'central_tenants' => []
    ],
    'laravel-package-generator' => [
        'patterns'      => [
            'project'   => [
                'generates'    => [
                    'provider' => ['type' => 'dir','path' => 'Providers','generate' => true, 'stub' => null, 'files' => [
                        '{{CLASS_BASENAME}}ServiceProvider' => ['type' => 'file','path' => '', 'generate' => true, 'stub' => '../MicroTenantStubs/project-microtenant-main-provider.php.stub'],
                    ]],
                    '{{CLASS_BASENAME}}'  => ['type' => 'file','path' => '', 'generate' => true, 'stub' => '../MicroTenantStubs/project-microtenant-main-class.php.stub'],
                ]
            ],
            'group'     => [
                'published_at' => app_path('Groups'),
                'generates'    => [
                    'migration'       => ['type' => 'dir','path' => 'Database/Migrations', 'generate' => true, 'stub' => null, 'files' => []],
                    'model'             => ['type' => 'dir','path' => 'Models','generate' => true, 'stub' => 'model.php.stub', 'files'=>[]],
                    'controller'        => ['type' => 'dir','path' => 'Controllers','generate' => false, 'stub' => null, 'files'=>[
                        'ApiController' => ['generate' => true, 'path' => 'API', 'stub' => 'project-api-controller.php.stub']
                    ]],
                    'provider'        => ['type' => 'dir','path' => 'Providers','generate' => true, 'stub' => null, 'files' => [
                        'CommandServiceProvider' => ['generate' => true, 'stub' => 'CommandServiceProvider.php.stub'],
                        'RouteServiceProvider'   => ['generate' => true, 'stub' => 'RouteServiceProvider.php.stub'],
                        '{{CLASS_BASENAME}}Environment' => ['generate' => true, 'stub' => 'project-EnvironmentServiceProvider.php.stub'],
                        '{{CLASS_BASENAME}}ServiceProvider' => ['type' => 'file','path' => '', 'generate' => true, 'stub' => 'project-main-provider.php.stub'],
                    ]],
                    'contract'        => ['type' => 'dir','path' => 'Contracts', 'generate' => true, 'stub' => null, 'files' => [
                        '{{CLASS_BASENAME}}'  => ['type' => 'file','path' => '', 'generate' => true, 'stub' => 'main-contract.php.stub'],
                    ]],
                    'concern'         => ['type' => 'dir','path' => 'Concerns', 'generate' => true, 'stub' => null, 'files' => []],
                    'command'         => ['type' => 'dir','path' => 'Commands', 'generate' => true, 'stub' => null, 'files' => [
                        'SeedCommand'    => ['generate' => true, 'stub' => 'SeedCommand.php.stub'],
                        'MigrateCommand' => ['generate' => true, 'stub' => 'MigrateCommand.php.stub'],
                        'InstallMakeCommand' => ['generate' => true, 'stub' => 'InstallMakeCommand.php.stub'],
                        'EnvironmentCommand' => ['generate' => true, 'stub' => 'project-EnvironmentCommand.php.stub']
                    ]],
                    'route'             => ['type' => 'dir','path' => 'Routes', 'generate' => true, 'stub' => null, 'files'=>[
                        'api' => ['generate' => true, 'stub' => 'api.php.stub']
                    ]],
                    'event'           => ['type' => 'dir','path' => 'Events', 'generate' => false, 'stub' => null, 'files' => []],
                    'observer'        => ['type' => 'dir','path' => 'Observers', 'generate' => true, 'stub' => null, 'files' => []],
                    'policy'          => ['type' => 'dir','path' => 'Policies', 'generate' => true, 'stub' => null, 'files' => []],
                    'job'             => ['type' => 'dir','path' => 'Jobs', 'generate' => false, 'stub' => null, 'files' => []],
                    'resource'        => ['type' => 'dir','path' => 'Resources', 'generate' => false, 'stub' => null, 'files' => []],
                    'seeder'          => ['type' => 'dir','path' => 'Database/Seeders', 'generate' => true, 'stub' => null, 'files' => []],
                    'middleware'      => ['type' => 'dir','path' => 'Middleware', 'generate' => true, 'stub' => null, 'files' => []],
                    'request'         => ['type' => 'dir','path' => 'Requests', 'generate' => true, 'stub' => null, 'files' => []],
                    'support'         => ['type' => 'dir','path' => 'Supports', 'generate' => true, 'stub' => null, 'files' => [
                        'PathRegistry' => ['generate' => true, 'stub' => 'PathRegistry.php.stub'],
                        'LocalPath'    => ['generate' => true, 'stub' => 'LocalPath.php.stub']
                    ]],
                    'view'            => ['type' => 'dir','path' => 'Views', 'generate' => true, 'stub' => null, 'files' => []],
                    'schema'          => ['type' => 'dir','path' => 'Schemas', 'generate' => true, 'stub' => null, 'files' => []],
                    'facade'          => ['type' => 'dir','path' => 'Facades', 'generate' => true, 'stub' => null, 'files' => [
                        '{{CLASS_BASENAME}}' => ['generate' => true, 'stub' => 'ModuleFacade.php.stub']
                    ]],
                    'config'          => ['type' => 'dir','path' => 'Config', 'generate' => true, 'stub' => null, 'files' => [
                        'config'        => ['generate' => true, 'stub' => 'project-config.php.stub']
                    ]],
                    'composer'          => ['type' => 'file','path' => '../', 'generate' => true, 'stub' => 'project-composer.json.stub', 'files'=>[]],
                    'helpers'           => ['type' => 'file','path' => '', 'generate' => true, 'stub' => 'helpers.php.stub', 'files'=>[]],
    
                    //FILE
                    'gitignore'          => ['filename' => '.gitignore','type' => 'file','path' => '', 'generate' => true, 'stub' => '.gitignore.stub'],
                    '{{CLASS_BASENAME}}'  => ['type' => 'file','path' => '', 'generate' => true, 'stub' => '../MicroTenantStubs/project-microtenant-main-class.php.stub'],
                ],
            ],
            'tenant'     => [
                'published_at' => app_path('Tenants'),
                'generates'    => [
                    'migration'       => ['type' => 'dir','path' => 'Database/Migrations', 'generate' => true, 'stub' => null, 'files' => []],
                    'model'             => ['type' => 'dir','path' => 'Models','generate' => true, 'stub' => 'model.php.stub', 'files'=>[]],
                    'controller'        => ['type' => 'dir','path' => 'Controllers','generate' => false, 'stub' => null, 'files'=>[
                        'ApiController' => ['generate' => true, 'path' => 'API', 'stub' => 'project-api-controller.php.stub']
                    ]],
                    'provider'        => ['type' => 'dir','path' => 'Providers','generate' => true, 'stub' => null, 'files' => [
                        'CommandServiceProvider' => ['generate' => true, 'stub' => 'CommandServiceProvider.php.stub'],
                        'RouteServiceProvider'   => ['generate' => true, 'stub' => 'RouteServiceProvider.php.stub'],
                        '{{CLASS_BASENAME}}Environment' => ['generate' => true, 'stub' => 'project-EnvironmentServiceProvider.php.stub'],
                        '{{CLASS_BASENAME}}ServiceProvider' => ['type' => 'file','path' => '', 'generate' => true, 'stub' => 'project-main-provider.php.stub'],
                    ]],
                    'contract'        => ['type' => 'dir','path' => 'Contracts', 'generate' => true, 'stub' => null, 'files' => [
                        '{{CLASS_BASENAME}}'  => ['type' => 'file','path' => '', 'generate' => true, 'stub' => 'main-contract.php.stub'],
                    ]],
                    'concern'         => ['type' => 'dir','path' => 'Concerns', 'generate' => true, 'stub' => null, 'files' => []],
                    'command'         => ['type' => 'dir','path' => 'Commands', 'generate' => true, 'stub' => null, 'files' => [
                        'SeedCommand'    => ['generate' => true, 'stub' => 'SeedCommand.php.stub'],
                        'MigrateCommand' => ['generate' => true, 'stub' => 'MigrateCommand.php.stub'],
                        'InstallMakeCommand' => ['generate' => true, 'stub' => 'InstallMakeCommand.php.stub'],
                        'EnvironmentCommand' => ['generate' => true, 'stub' => 'project-EnvironmentCommand.php.stub']
                    ]],
                    'route'             => ['type' => 'dir','path' => 'Routes', 'generate' => true, 'stub' => null, 'files'=>[
                        'api' => ['generate' => true, 'stub' => 'api.php.stub']
                    ]],
                    'event'           => ['type' => 'dir','path' => 'Events', 'generate' => false, 'stub' => null, 'files' => []],
                    'observer'        => ['type' => 'dir','path' => 'Observers', 'generate' => true, 'stub' => null, 'files' => []],
                    'policy'          => ['type' => 'dir','path' => 'Policies', 'generate' => true, 'stub' => null, 'files' => []],
                    'job'             => ['type' => 'dir','path' => 'Jobs', 'generate' => false, 'stub' => null, 'files' => []],
                    'resource'        => ['type' => 'dir','path' => 'Resources', 'generate' => false, 'stub' => null, 'files' => []],
                    'seeder'          => ['type' => 'dir','path' => 'Database/Seeders', 'generate' => true, 'stub' => null, 'files' => []],
                    'middleware'      => ['type' => 'dir','path' => 'Middleware', 'generate' => true, 'stub' => null, 'files' => []],
                    'request'         => ['type' => 'dir','path' => 'Requests', 'generate' => true, 'stub' => null, 'files' => []],
                    'support'         => ['type' => 'dir','path' => 'Supports', 'generate' => true, 'stub' => null, 'files' => [
                        'PathRegistry' => ['generate' => true, 'stub' => 'PathRegistry.php.stub'],
                        'LocalPath'    => ['generate' => true, 'stub' => 'LocalPath.php.stub']
                    ]],
                    'view'            => ['type' => 'dir','path' => 'Views', 'generate' => true, 'stub' => null, 'files' => []],
                    'schema'          => ['type' => 'dir','path' => 'Schemas', 'generate' => true, 'stub' => null, 'files' => []],
                    'facade'          => ['type' => 'dir','path' => 'Facades', 'generate' => true, 'stub' => null, 'files' => [
                        '{{CLASS_BASENAME}}' => ['generate' => true, 'stub' => 'ModuleFacade.php.stub']
                    ]],
                    'config'          => ['type' => 'dir','path' => 'Config', 'generate' => true, 'stub' => null, 'files' => [
                        'config'        => ['generate' => true, 'stub' => 'project-config.php.stub']
                    ]],
                    'composer'          => ['type' => 'file','path' => '../', 'generate' => true, 'stub' => 'project-composer.json.stub', 'files'=>[]],
                    'helpers'           => ['type' => 'file','path' => '', 'generate' => true, 'stub' => 'helpers.php.stub', 'files'=>[]],
    
                    //FILE
                    'gitignore'          => ['filename' => '.gitignore','type' => 'file','path' => '', 'generate' => true, 'stub' => '.gitignore.stub'],
                    '{{CLASS_BASENAME}}'  => ['type' => 'file','path' => '', 'generate' => true, 'stub' => '../MicroTenantStubs/project-microtenant-main-class.php.stub'],
                ],
            ]
        ],
    ],
    'commands' => [
        Commands\Impersonate\ImpersonateCacheCommand::class,
        Commands\Impersonate\ImpersonateMigrateCommand::class,
        Commands\InstallMakeCommand::class,
        Commands\AddTenantCommand::class,
        Commands\AddPackageCommand::class
    ],
    'payload_monitoring' => [
        'enabled'     => true,
        'categories'  => [
            'slow'    => 1000, // in miliseconds
            'medium'  => 500,
            'fast'    => 100
        ]
    ],
    /**
     * The list of packages will be added when the system is run, based on the installed features related to the tenant.
     */
    'package_list' => [],
    'tenancy' => [
        'enabled' => true,
        
        /**
         * Tenancy bootstrappers are executed when tenancy is initialized.
         * Their responsibility is making Laravel features tenant-aware.
         *
         * To configure their behavior, see the config keys below.
         */
        'bootstrappers' => [
            Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
            Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
            Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
            Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
            // Stancl\Tenancy\Bootstrappers\RedisTenancyBootstrapper::class, // Note: phpredis is needed
        ],
        /**
         * Cache tenancy config. Used by CacheTenancyBootstrapper.
         *
         * This works for all Cache facade calls, cache() helper
         * calls and direct calls to injected cache stores.
         *
         * Each key in cache will have a tag applied on it. This tag is used to
         * scope the cache both when writing to it and when reading from it.
         *
         * You can clear cache selectively by specifying the tag.
         */
        'cache' => [
            'authorization' => [
                'prefix' => 'microtenant_'
            ],
            'tag_base' => 'microtenant', // This tag_base, followed by the tenant_id, will form a tag that will be applied on each cache call.
        ],

        /**
         * Filesystem tenancy config. Used by FilesystemTenancyBootstrapper.
         * https://tenancyforlaravel.com/docs/v3/tenancy-bootstrappers/#filesystem-tenancy-boostrapper.
         */
        'filesystem' => [
            /**
             * Each disk listed in the 'disks' array will be suffixed by the suffix_base, followed by the tenant_id.
             */
            'suffix_base' => 'microtenant',
            'disks' => [
                'local',
                'public',
                // 's3',
            ],

            /**
             * Use this for local disks.
             *
             * See https://tenancyforlaravel.com/docs/v3/tenancy-bootstrappers/#filesystem-tenancy-boostrapper
             */
            'root_override' => [
                // Disks whose roots should be overridden after storage_path() is suffixed.
                'local' => '%storage_path%/app/',
                'public' => '%storage_path%/app/public/',
            ],

            /**
             * Should storage_path() be suffixed.
             *
             * Note: Disabling this will likely break local disk tenancy. Only disable this if you're using an external file storage service like S3.
             *
             * For the vast majority of applications, this feature should be enabled. But in some
             * edge cases, it can cause issues (like using Passport with Vapor - see #196), so
             * you may want to disable this if you are experiencing these edge case issues.
             */
            'suffix_storage_path' => true,

            /**
             * By default, asset() calls are made multi-tenant too. You can use global_asset() and mix()
             * for global, non-tenant-specific assets. However, you might have some issues when using
             * packages that use asset() calls inside the tenant app. To avoid such issues, you can
             * disable asset() helper tenancy and explicitly use tenant_asset() calls in places
             * where you want to use tenant-specific assets (product images, avatars, etc).
             */
            'asset_helper_tenancy' => true,
        ],

        /**
         * Redis tenancy config. Used by RedisTenancyBootstrapper.
         *
         * Note: You need phpredis to use Redis tenancy.
         *
         * Note: You don't need to use this if you're using Redis only for cache.
         * Redis tenancy is only relevant if you're making direct Redis calls,
         * either using the Redis facade or by injecting it as a dependency.
         */
        'redis' => [
            'prefix_base' => 'microtenant', // Each key in Redis will be prepended by this prefix_base, followed by the tenant id.
            'prefixed_connections' => [ // Redis connections whose keys are prefixed, to separate one tenant's keys from another.
                // 'default',
            ],
        ],

        /**
         * Features are classes that provide additional functionality
         * not needed for tenancy to be bootstrapped. They are run
         * regardless of whether tenancy has been initialized.
         *
         * See the documentation page for each class to
         * understand which ones you want to enable.
         */
        'features' => [
            // Stancl\Tenancy\Features\UserImpersonation::class,
            // Stancl\Tenancy\Features\TelescopeTags::class,
            // Stancl\Tenancy\Features\UniversalRoutes::class,
            // Stancl\Tenancy\Features\TenantConfig::class, // https://tenancyforlaravel.com/docs/v3/features/tenant-config
            // Stancl\Tenancy\Features\CrossDomainRedirect::class, // https://tenancyforlaravel.com/docs/v3/features/cross-domain-redirect
            // Stancl\Tenancy\Features\ViteBundler::class,
        ],

        /**
         * Should tenancy routes be registered.
         *
         * Tenancy routes include tenant asset routes. By default, this route is
         * enabled. But it may be useful to disable them if you use external
         * storage (e.g. S3 / Dropbox) or have a custom asset controller.
         */
        'routes' => true,

        'migration_parameters' => [
            '--force' => true, // This needs to be true to run migrations in production.
            '--path' => [
                //Some migrations will be automatically read based on the app, tenant, and module version managed by microtenant
                database_path('migrations/tenant'),
            ],
            '--realpath' => true,
        ],

        'seeder_parameters' => [
            '--class' => 'DatabaseSeeder', // root seeder class
            // '--force' => true,
        ],
    ]
];
