<?php

namespace App\Http\Resources\API\ApiAccess;

use App\Http\Resources\API\ApiResource;

class HqGenerateTokenResponse extends ApiResource
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
                    return $this->chooseReference($this->userReference);
                }),
                'user_references' => $this->relationValidation('userReferences',function(){
                    return $this->userReferences->transform(function($userReference){
                        return $this->chooseReference($userReference);
                    });
                })
            ]
        ];
    }

    private function chooseReference($userReference){
        return [
            'id'             => $userReference->id,
            'uuid'           => $userReference->uuid,
            'reference_type' => $userReference->reference_type,
            'reference_id'   => $userReference->reference_id,
            'current'        => $userReference->current,
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
            'role' => $userReference->prop_role
        ];
    }
}
