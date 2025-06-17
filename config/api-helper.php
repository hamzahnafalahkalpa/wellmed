<?php

use Hanafalah\ApiHelper\{
    Commands,
    Middlewares,
    Encryptions,
    Validators
};
use Hanafalah\ModuleUser\Models\User\User;

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
        'model'     => User::class,
        'keys'      => ['username'],
        'password'  => 'password'
    ],

    'expiration' => null,

    /*
     * Supported algorithms.
     *
     * @see https://tools.ietf.org/html/rfc7518#section-3.1
     */
    'encryption_method' => 'HS256',
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
