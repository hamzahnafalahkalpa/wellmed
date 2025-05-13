<?php

namespace App\Http\Resources\API;

use Hanafalah\LaravelSupport\Resources\ApiResource as SupportApiResource;

class ApiResource extends SupportApiResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(\Illuminate\Http\Request $request): array
    {
        return parent::toArray($request);
    }
}