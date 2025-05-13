<?php

use Hanafalah\ModuleRegional\Commands;

return [
    'namespace' => 'Hanafalah\\ModuleRegional',
    'app' => [
        'contracts' => [
            //ADD YOUR CONTRACTS HERE
        ],
    ],
    'libs' => [
        'model' => 'Models',
        'contract' => 'Contracts',
        'schema' => 'Schemas',
        'database' => 'Database',
        'data' => 'Data',
        'resource' => 'Resources',
        'migration' => '../assets/database/migrations'
    ],
    'database' => [
        'models' => [
            //ADD YOUR MODEL HERE
        ]
    ],
    'commands' => [
        Commands\InstallMakeCommand::class
    ]
];
