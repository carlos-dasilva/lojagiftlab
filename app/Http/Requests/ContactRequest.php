<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:100', 'email' => 'required|email|max:150', 'subject' => 'required|string|max:150', 'message' => 'required|string|min:10|max:5000', 'website' => 'nullable|size:0'];
    }
}
