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
                        'id'             => $userReference->id,
                        'uuid'           => $userReference->uuid,
                        'tenant'    => $userReference->relationValidation('tenant',function() use ($userReference){
                            $tenant = $userReference->tenant;
                            return [
                                'id'        => $tenant->id,
                                'name'      => $tenant->name,
                                'workspace' => $tenant->relationValidation('reference',function() use ($tenant){
                                    $reference = $tenant->reference;
                                    return [
                                        'id'   => $reference->id,
                                        'uuid' => $reference->uuid,
                                        'name' => $reference->name
                                    ];
                                }),
                                'domain'    => $tenant->prop_domain
                            ];
                        }),
                        'role' => $userReference->prop_role,
                        'roles' => $userReference->relationValidation('roles',function() use ($userReference){
                            $roles = $userReference->roles;
                            return $roles->transform(function($role) use ($userReference){
                                return [
                                    'id'   => $role->id,
                                    'name' => $role->name
                                ];
                            });
                        })
                    ];
                })
            ]
        ];
    }
}
