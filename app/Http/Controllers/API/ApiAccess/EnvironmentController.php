<?php

namespace App\Http\Controllers\API\ApiAccess;

use App\Http\Controllers\ApiController;
use Illuminate\Database\Eloquent\Model;
use Hanafalah\ApiHelper\Facades\ApiAccess;

class EnvironmentController extends ApiController{

    protected Model $__user;

    /**
     * Generate a new token, which is a JTI.
     *
     * @return string The JTI
     */
    protected function generateToken(){
        $token = ApiAccess::generateToken(function($api_access){
            $this->__user = $api_access->getUser();
        });
        return $token;
    }
    
    protected function getUser(){
        $this->__user->load([
            'userReference'=>function($query){
                $query->with([
                    'reference','roles','workspace'
                ]);
            }
        ]);
        if (isset($this->__user->userReference)){
            $user_reference = &$this->__user->userReference;
            if (!isset($user_reference->role)){
                $user_reference->modelHasRole()->update([
                    'model_id'   => $user_reference->getKey(),
                    'model_type' => $user_reference->getMorphClass(),
                    'current'    => 0
                ]);
                $model_has_role = $user_reference->modelHasRole()->first();
                if (isset($model_has_role)){
                    $model_has_role->current = 1;
                    $model_has_role->save();
                }
                $user_reference->load('role');
            }
        }
        return $this->__user;
    }


}