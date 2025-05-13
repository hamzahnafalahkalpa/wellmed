<?php

use Hanafalah\LaravelPermission\{
    Models,
    Commands,
    Contracts
};

return [
    'namespace' => 'Hanafalah\\LaravelPermission',
    'app' => [
        'contracts' => [
            //ADD YOUR CONTRACTS HERE
        ]
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
            //ADD YOUR MODELS HERE
        ]
    ],
    'commands' => [
        Commands\InstallMakeCommand::class
    ]
];
