<?php

use Hanafalah\LaravelStub\Commands\InstallMakeCommand;

return [
    'libs'       => [
        'model' => 'Models',
        'contract' => 'Contracts',
        'schema' => 'Schemas',
        'database' => 'Database',
        'data' => 'Data',
        'resource' => 'Resources',
        'migration' => '../assets/database/migrations'
    ],
    'stub' => [
        'open_separator'  => '{{',
        'close_separator' => '}}',
        'path'            => base_path('stubs'),
    ],
    'commands'        => [
        InstallMakeCommand::class
    ],
];
