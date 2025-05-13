<?php

use Hanafalah\ApiHelper\{
    Commands,
    Models,
    Middlewares,
    Encryptions,
    Validators
};

return [
    'commands' => [
        Commands\InstallMakeCommand::class,
        Commands\GenerateRsKeyCommand::class,
        Commands\ApiAccessMakeCommand::class
    ],
    'routes' => [
        'prefix' => 'api'
    ],

    'middlewares' => [
        Middlewares\ApiAccess::class
    ],

    'encryption'    => Encryptions\JWTEncryptor::class,
    // 'encryption' => Encryptions\DefaultEncryptor::class,

    'authorizing'   => Validators\JWTTokenValidator::class,

    'authorization_model' => [
        // this is the setup for authentication user,
        // it can have more than one key, the system will do loop check
        // and the password for credential password
        'model'     => App\Models\User::class,
        'keys'      => ['name'],
        'password'  => 'password'
    ],

    'expiration' => 3600,

    /*
     * Supported algorithms.
     *
     * @see https://tools.ietf.org/html/rfc7518#section-3.1
     */
    'encryption_method' => 'RS256',
    'libs' => [
        'model' => 'Models',
        'contract' => 'Contracts',
        'schema' => 'Schemas',
        'database' => 'Database'
    ],
    'database' => [
        'models' => [
            //ADD YOUR MODELS HERE
        ]
    ]
];
