<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
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
            'phone' => ['required', 'string', 'regex:/^79[0-9]{9}$/', 'unique:users,phone'],
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['nullable', 'string', 'in:M,F'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
                'confirmed',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/'
            ],
            'marketing_agree' => ['required', 'boolean'],
            'privacy_agree' => ['required', 'boolean'],
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
            'phone.unique' => 'Поле "Телефон". Пользователь с таким номером уже существует.',
            'phone.string' => 'Поле "Телефон" должно быть строкой.',
            'phone.regex' => 'Номер телефона должен начинаться с 79 и содержать 11 цифр.',
            'name.required' => 'Поле "Имя" обязательно для заполнения.',
            'name.string' => 'Поле "Имя" должно быть строкой.',
            'name.max' => 'Поле "Имя" не должно превышать 255 символов.',
            'last_name.string' => 'Поле "Фамилия" должно быть строкой.',
            'last_name.max' => 'Поле "Фамилия" не должно превышать 255 символов.',
            'email.email' => 'E-mail введен некорректно. Введи еще раз.',
            'email.max' => 'Поле "Email" не должно превышать 255 символов.',
            'gender.in' => 'Поле "Пол" должно быть "M" - male или "F" - female.',
            'birthday.date' => 'Поле "Дата рождения" должно быть датой.',
            'password.required' => 'Поле "Пароль" обязательно для заполнения.',
            'password.min' => 'Поле "Пароль" должно содержать минимум 8 символов.',
            'password.max' => 'Поле "Пароль" не должно превышать 255 символов.',
            'password.confirmed' => 'Пароли не совпадают',
            'password.regex' => 'Пароль должен содержать: строчные и заглавные буквы, цифры и спецсимвол (@$!%*#?&)'
        ];
    }
}
