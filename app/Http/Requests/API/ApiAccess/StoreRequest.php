<?php

namespace App\Http\Requests\API\ApiAccess;

use Hanafalah\ApiHelper\Facades\ApiAccess;
use Hanafalah\LaravelSupport\Requests\FormRequest;
use Hanafalah\MicroTenant\Facades\MicroTenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreRequest extends FormRequest
{
    protected $__entity = 'User';

    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', $this->existsValidation('User','username')],
            'password' => ['required', 'string']
        ];
    }

        /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('username', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        
        $user = Auth::user();
        dd($user);
        $token = $user->createToken('api-token')->plainTextToken;
        request()->headers->set('Authorization', $token);

        ApiAccess::init()->accessOnLogin(function ($api_access) {
            MicroTenant::onLogin($api_access);
        });

        RateLimiter::clear($this->throttleKey());
    }
}