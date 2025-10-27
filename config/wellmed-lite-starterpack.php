<?php

use Hanafalah\WellmedLiteStarterpack\{
    Models,
    Commands,
    Contracts
};

return [
    "namespace"     => "Hanafalah\WellmedLiteStarterpack",
    "paths"         => [
        "local_path"   => "repositories",
        "base_path"    => __DIR__.'\\..\\'
    ],
    'app' => [
        'contracts' => [
        ],
    ],
    'commands'  => [
        Commands\SeedCommand::class,
        Commands\MigrateCommand::class,
        Commands\InstallMakeCommand::class
    ],
    'libs' => [
        'asset' => '../assets',
        'config' => '../assets/config',
        'migration' => '../assets/database/migrations',
        'model' => 'Models',
        'controller' => 'Controllers',
        'provider' => 'Providers',
        'contract' => 'Contracts',
        'concern' => 'Concerns',
        'command' => 'Commands',
        'route' => 'Routes',
        'import' => 'Imports',
        'data' => 'Data',
        'resource' => 'Resources',
        'seeder' => 'Database/Seeders',
        'middleware' => 'Middleware',
        'request' => 'Requests',
        'support' => 'Supports',
        'schema' => 'Schemas',
        'facade' => 'Facades',
    ],
    'database' => [
        'models' => [
        ]
    ],
    "provider" => "Hanafalah\WellmedLiteStarterpack\\Providers\\WellmedLiteStarterpackServiceProvider",
    'packages' => [
        // 'hanafalah/api-helper'                  => ['repository' =>'hamzahnafalahkalpa/api-helper'],
        // 'hanafalah/laravel-has-props'           => ['repository' =>'hamzahnafalahkalpa/laravel-has-props'],
        // 'hanafalah/laravel-package-generator'   => ['repository' =>'hamzahnafalahkalpa/laravel-package-generator'],
        // 'hanafalah/laravel-permission'          => ['repository' =>'hamzahnafalahkalpa/laravel-permission'],
        // 'hanafalah/laravel-stub'                => ['repository' =>'hamzahnafalahkalpa/laravel-stub'],
        // 'hanafalah/laravel-support'             => ['repository' =>'hamzahnafalahkalpa/laravel-support'],
        // 'hanafalah/microtenant'                 => ['repository' =>'hamzahnafalahkalpa/microtenant'],        
        'hanafalah/laravel-feature'             => ['repository' =>'hamzahnafalahkalpa/laravel-feature'],
        'hanafalah/module-payer'                => ['repository' =>'hamzahnafalahkalpa/module-payer'],
        'hanafalah/module-organization'         => ['repository' =>'hamzahnafalahkalpa/module-organization'],
        'hanafalah/module-encoding'             => ['repository' =>'hamzahnafalahkalpa/module-encoding'],
        'hanafalah/module-regional'             => ['repository' =>'hamzahnafalahkalpa/module-regional'],
        'hanafalah/module-user'                 => ['repository' =>'hamzahnafalahkalpa/module-user'],
        'hanafalah/module-workspace'            => ['repository' =>'hamzahnafalahkalpa/module-workspace'],
        'hanafalah/module-patient'              => ['repository' =>'hamzahnafalahkalpa/module-patient'],
        'hanafalah/module-employee'             => ['repository' =>'hamzahnafalahkalpa/module-employee'],
        'hanafalah/module-funding'              => ['repository' =>'hamzahnafalahkalpa/module-funding'],
        'hanafalah/module-medic-service'        => ['repository' =>'hamzahnafalahkalpa/module-medic-service'],
        'hanafalah/module-medical-treatment'    => ['repository' =>'hamzahnafalahkalpa/module-medical-treatment'],
        'hanafalah/module-payment'              => ['repository' =>'hamzahnafalahkalpa/module-payment'],
        'hanafalah/module-people'               => ['repository' =>'hamzahnafalahkalpa/module-people'],
        'hanafalah/module-card-identity'        => ['repository' =>'hamzahnafalahkalpa/module-card-identity'],
        'hanafalah/module-profession'           => ['repository' =>'hamzahnafalahkalpa/module-profession'],
        'hanafalah/module-regional'             => ['repository' =>'hamzahnafalahkalpa/module-regional'],
        'hanafalah/module-service'              => ['repository' =>'hamzahnafalahkalpa/module-service'],
        'hanafalah/module-summary'              => ['repository' =>'hamzahnafalahkalpa/module-summary'],
        'hanafalah/module-support'              => ['repository' =>'hamzahnafalahkalpa/module-support'],
        'hanafalah/module-transaction'          => ['repository' =>'hamzahnafalahkalpa/module-transaction'],
        'hanafalah/module-treatment'            => ['repository' =>'hamzahnafalahkalpa/module-treatment'],
        'hanafalah/module-warehouse'            => ['repository' =>'hamzahnafalahkalpa/module-warehouse'],
        'hanafalah/module-workspace'            => ['repository' =>'hamzahnafalahkalpa/module-workspace'],
        'hanafalah/module-item'                 => ['repository' =>'hamzahnafalahkalpa/module-item'],
        'hanafalah/module-medical-item'         => ['repository' =>'hamzahnafalahkalpa/module-medical-item'],
        'hanafalah/module-examination'          => ['repository' =>'hamzahnafalahkalpa/module-examination'],
        'hanafalah/module-disease'              => ['repository' =>'hamzahnafalahkalpa/module-disease'],
        'hanafalah/module-informed-consent'     => ['repository' =>'hamzahnafalahkalpa/module-informed-consent'],
        'hanafalah/module-icd'                  => ['repository' =>'hamzahnafalahkalpa/module-icd'],
        'hanafalah/module-anatomy'              => ['repository' =>'hamzahnafalahkalpa/module-anatomy'],
        'hanafalah/module-physical-examination' => ['repository' =>'hamzahnafalahkalpa/module-physical-examination'],
        'hanafalah/module-pharmacy'             => ['repository' =>'hamzahnafalahkalpa/module-pharmacy'],
        'hanafalah/module-tax'                  => ['repository' =>'hamzahnafalahkalpa/module-tax'],
        'hanafalah/module-handwriting'          => ['repository' =>'hamzahnafalahkalpa/module-handwriting'],
        'hanafalah/wellmed-feature'             => ['repository' =>'hamzahnafalahkalpa/wellmed-feature'],
        'hanafalah/satu-sehat'                  => ['repository' =>'hamzahnafalahkalpa/satu-sehat'],
        'wellmed-lite/ms-apotek'                => ['repository' =>'wellmed-lite/ms-apotek'],
        'wellmed-lite/ms-emr'                   => ['repository' =>'wellmed-lite/ms-emr'],
        'wellmed-lite/ms-hr'                    => ['repository' =>'wellmed-lite/ms-hr'],
        'wellmed-lite/ms-point-of-sale'         => ['repository' =>'wellmed-lite/ms-point-of-sale'],
        'wellmed-lite/ms-scm'                   => ['repository' =>'wellmed-lite/ms-scm']
    ]
];
