<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^79[0-9]{9}$/', 'exists:users,phone'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'Поле "Телефон" обязательно для заполнения.',
            'phone.string' => 'Поле "Телефон" должно быть строкой.',
            'phone.regex' => 'К сожалению, формат номера неверный. Попробуй ввести его еще раз. Лучше всего делать это в формате: 79ХХХХХХХХХ',
            'phone.exists' => 'К сожалению, мне не удалось ничего найти. Ты уверен, что номер правильный? Попробуй ввести его еще раз. Лучше всего делать это в формате: 79ХХХХХХХХХ.',
        ];
    }
}
