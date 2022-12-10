<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CoachSignUpRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users', 'unique:coaches'],
            'phone_number' => ['required', 'numeric'],
            'description' => ['required', 'string', 'max:255'],
            'image' => ['file', 'mimes:jpg,jpeg,png', 'max:1024'],
            'password' => ['required', 'string', 'min:8', 'max:50', 'confirmed'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Tidak boleh kosong',
            'name.string' => 'Harus string',
            'name.max' => 'Maximal 50 huruf',
            'phone_number.required' => 'Tidak boleh kosong',
            'phone_number.numerik' => 'Harus numerik',
            'description.required' => 'Tidak boleh kosong',
            'description.string' => 'Harus string',
            'description.max' => 'Maximal 255',
            'image.file' => 'Bentuk harus file',
            'image.mimes' => 'Hanya boleh mengunggah gambar',
            'image.max' => 'Maximal 1MB',
        ];
    }
}
