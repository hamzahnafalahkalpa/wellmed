<?php

namespace App\Http\Controllers\API\ApiAccess;

use App\Http\Requests\API\ApiAccess\{
    StoreRequest, DeleteRequest
};
use App\Http\Resources\API\ApiAccess\GenerateTokenResponse;
use Hanafalah\ModuleEmployee\Models\Employee\Employee;
use Illuminate\Support\Facades\Auth;

class ApiAccessController extends EnvironmentController{
    public function store(StoreRequest $request){
        $request->authenticate();
        $request->session()->regenerate();

        

        $token = $this->generateToken();
        $user  = $this->getUser();
        $response = $this->retransform($user, function ($user) use ($token) {
            $user->token = $token;
            return new GenerateTokenResponse($user);
        });
        return $response;
    }

    public function destroy(DeleteRequest $request){
        Auth::logout();
        // $request->session()->invalidate();
        // $request->session()->regenerateToken();
        return response()->noContent();
    }
}
