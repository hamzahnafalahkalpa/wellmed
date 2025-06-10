<?php

namespace App\Http\Requests\API\ApiAccess;

use Hanafalah\LaravelSupport\Requests\FormRequest;

class DeleteRequest extends FormRequest
{
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
     * @return array
     */
    public function rules()
    {
        return [
            'uuid' => ['required',$this->uuidValidation('UserReference')]
        ];
    }

    /**
     * Custom message for validation
     * 
     * @return array
     */
    public function messages()
    {
        return [
            'uuid.required' => 'UUID wajib dikirim sebagai parameter',
            'uuid.exists'   => 'UUID tidak ditemukan di database',
        ];
    }
}
