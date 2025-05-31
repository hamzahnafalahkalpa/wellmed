<?php

namespace App\Middlewares;

use App\Middlewares\RedirectIfAuthenticated;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Configuration\Middleware as ConfigurationMiddleware;
use Illuminate\Session\Middleware\AuthenticateSession;

class Middleware extends ConfigurationMiddleware
{
    public function redirectTo(callable|string|null $guests = null, callable|string|null $users = null)
    {
        $guests = is_string($guests) ? fn () => $guests : $guests;
        $users = is_string($users) ? fn () => $users : $users;

        if ($guests) {
            Authenticate::redirectUsing($guests);
            AuthenticateSession::redirectUsing($guests);
            AuthenticationException::redirectUsing($guests);
        }

        if ($users) {
            RedirectIfAuthenticated::redirectUsing($users);
        }

        return $this;
    }
}
