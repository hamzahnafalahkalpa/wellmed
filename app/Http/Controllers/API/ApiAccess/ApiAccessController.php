<?php

namespace App\Http\Controllers\API\ApiAccess;

use App\Http\Requests\API\ApiAccess\{
    StoreRequest, DeleteRequest
};
use App\Http\Resources\API\ApiAccess\GenerateTokenResponse;
use Hanafalah\ApiHelper\Facades\ApiAccess;
use Hanafalah\LaravelSupport\Concerns\Support\HasCache;
use Hanafalah\MicroTenant\Facades\MicroTenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ApiAccessController extends EnvironmentController
{
    use HasCache;

    public function store(StoreRequest $request)
    {
        $token = $this->generateToken();
        if (isset($token) && $request->headers->has('AppCode')) {
            $this->__user->load([
                'userReference.tenant' => function($query){
                    $query->with(['domain','reference']);
                }
            ]);
            if (config('octane') !== null) {
                $tenant = $this->__user->userReference->tenant;
                MicroTenant::tenantImpersonate($tenant);
                // MicroTenant::accessOnLogin($token);
            } else {
                ApiAccess::init($token)->accessOnLogin(function ($api_access) {
                    MicroTenant::onLogin($api_access);
                });
            }
        }
        $user = $this->getUser();
        // Jika mau return token
        $user->token = $token;
        return (new GenerateTokenResponse($user))->resolve();
    }

    public function refresh(Request $request)
    {
        $token = $this->generateToken();
        return [
            'token' => $token
        ];
    }

    public function destroy(DeleteRequest $request)
    {
        Auth::logout();
        return true;
    }
}
