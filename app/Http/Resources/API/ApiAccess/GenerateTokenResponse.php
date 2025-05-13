<?php

namespace App\Http\Resources\API\ApiAccess;

use App\Http\Resources\API\ApiResource;

class GenerateTokenResponse extends ApiResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    private static $token = null;

    public function toArray(\Illuminate\Http\Request $request): array
    {
        return [
            'token'  => $this->token,
            'user'   => [
                'id'             => $this->id,
                'username'       => $this->username,
                'email'          => $this->email,
                'user_reference' => $this->relationValidation('userReference',function(){
                    $userReference = $this->userReference;
                    return [
                        'uuid'           => $userReference->uuid,
                        'reference_id'   => $userReference->reference_id,
                        'reference_type' => $userReference->reference_type,
                        'reference'      => $userReference->relationValidation('reference',function() use ($userReference){
                            $reference = $userReference->reference;
                            return $reference->toViewApi();
                        }),
                        'workspace'    => $userReference->relationValidation('workspace',function() use ($userReference){
                            $workspace = $userReference->workspace;
                            return $workspace->toShowApi();
                        }),
                        'role' => $userReference->prop_role,
                        'roles' => $userReference->relationValidation('roles',function() use ($userReference){
                            $roles = $userReference->roles;
                            return $roles->transform(function($role) use ($userReference){
                                return $role->toViewApi();
                            });
                        })
                    ];
                })
            ]
        ];
    }
}
