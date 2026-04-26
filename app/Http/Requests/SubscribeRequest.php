<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'url'   => 'required|url|regex:/olx\.ua/',
            'email' => 'required|email',
        ];
    }
}
