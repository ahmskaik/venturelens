<?php

namespace App\Http\Requests;

class UpdateApplicationRequest extends StoreApplicationRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['founder_email']);

        return $rules;
    }
}
