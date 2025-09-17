<?php

use WellmedLite\TenantWellmedLite\{
    Contracts, Models, Commands
};

return [
    "namespace"     => "WellmedLite\TenantWellmedLite",
    "service_name"  => "TenantWellmedLite",
    "paths"         => [
        "local_path"   => 'tenants',
        "base_path"    => __DIR__.'\\..\\'
    ],
    "libs"           => [
        'migration' => 'Database/Migrations',
        'model' => 'Models',
        'provider' => 'Providers',
        'contract' => 'Contracts',
        'concern' => 'Concerns',
        'command' => 'Commands',
        'route' => 'Routes',
        'observer' => 'Observers',
        'policy' => 'Policies',
        'transformer' => 'Transformers',
        'seeder' => 'Database/Seeders',
        'middleware' => 'Middleware',
        'request' => 'Requests',
        'support' => 'Supports',
        'view' => 'Views',
        'schema' => 'Schemas',
        'facade' => 'Facades',
        'config' => 'Config',
    ],
    "packages" => [
        /*--------------------------------------------------------------------------
        * Note: The contents of the packages are started with the class base name,
        * then followed by config and others. You can be used to override default package config
        * "module-user" => [
        *       "config" => []
        * ]
        *------------------------------------------------------------------------*/
    ],
    "app" => [
        "impersonate" => [
            "storage"   => [
                "driver" => env("FILESYSTEM_DISK", 'local'),
            ],
        ],
        "contracts" => [
        ],
    ],
    "database"     => [
        "models"   => [
        ]
    ],
    "commands" => [
        Commands\SeedCommand::class,
        Commands\MigrateCommand::class,
        Commands\InstallMakeCommand::class
    ],
    "encodings" => [
    ],
    "provider" => "WellmedLite\TenantWellmedLite\\Providers\\TenantWellmedLiteServiceProvider"
];
