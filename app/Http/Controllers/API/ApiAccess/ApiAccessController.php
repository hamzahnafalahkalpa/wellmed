<?php

namespace App\Http\Controllers\API\ApiAccess;

use App\Http\Requests\API\ApiAccess\{
    StoreRequest, DeleteRequest
};
use App\Http\Resources\API\ApiAccess\GenerateTokenResponse;
use Hanafalah\ApiHelper\Facades\ApiAccess;
use Hanafalah\MicroTenant\Facades\MicroTenant;
use Illuminate\Support\Facades\Auth;

class ApiAccessController extends EnvironmentController{
    
    public function store(StoreRequest $request){
        $token = $this->generateToken();
        if (isset($token) && request()->headers->has('AppCode')) {
            ApiAccess::init($token)->accessOnLogin(function ($api_access) {
                MicroTenant::onLogin($api_access);
            });
        }
        $user        = $this->getUser();
        $user->token = $token;
        return (new GenerateTokenResponse($user))->resolve();
    }

    public function destroy(DeleteRequest $request){
        Auth::logout();
        return true;
    }
}