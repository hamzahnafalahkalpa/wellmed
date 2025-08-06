<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Hanafalah\ApiHelper\Concerns\Token\HasApiTokens;
use Hanafalah\ModuleUser\Models\User\User as UserUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends UserUser
{
    use HasFactory, Notifiable, HasApiTokens;
}
