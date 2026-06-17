<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UploadPhotoRequest extends FormRequest
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
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
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
            'photo.required' => 'Не совсем понимаю. Для того, чтобы зарегистрировать чек, отправь мне его скан/фото.',
            'photo.image' => 'К сожалению, файл, который ты пытаешься загрузить, не соответствует требуемым форматам.',
            'photo.mimes' => 'Ошибка загрузки файла: допустимые форматы файлов — jpg, jpeg, png.',
            'photo.max' => 'Ошибка загрузки: Максимальный размер файла 5Mb.',
        ];
    }
}
