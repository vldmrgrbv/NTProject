<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SubmitManualRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fn' => ['required', 'string'],
            'fd' => ['required', 'string'],
            'fp' => ['required', 'string'],
            'sum' => ['required', 'numeric', 'regex:/^\d+\.\d{2}$/'], // Формат 100.00
            'dt' => ['required', 'string', 'date_format:Y-m-d\TH:i:s'], // Формат YYYY-MM-DDTHH:MM:SS
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
            'fn.required' => 'Поле "fn" обязательно для заполнения.',
            'fn.string' => 'Поле "fn" должно быть строкой.',
            'fd.required' => 'Поле "fd" обязательно для заполнения.',
            'fd.string' => 'Поле "fd" должно быть строкой.',
            'fp.required' => 'Поле "fp" обязательно для заполнения.',
            'fp.string' => 'Поле "fp" должно быть строкой.',
            'sum.required' => 'Поле "Сумма" обязательно для заполнения.',
            'sum.numeric' => 'Поле "Сумма" должно быть числом.',
            'sum.regex' => 'Поле "Сумма" должно быть суммой с копейками (.00).',
            'dt.required' => 'Поле "Дата" обязательно для заполнения.',
            'dt.string' => 'Поле "Дата" должно быть строкой.',
            'dt.date_format' => 'Поле "Дата" должно быть строкой в формате YYYY-MM-DDTHH:MM:SS.',
        ];
    }
}
