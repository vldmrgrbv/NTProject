<?php

namespace App\Enums;

enum UserEventType: string
{
    case REG = 'reg';
    case AUTH = 'auth';
    case UPDATE_PHONE = 'update_phone';
    case BUTTON_CLICK = 'button_click';
    case START = '/start';

    public function toString(): string
    {
        return match ($this) {
            self::REG => 'Регистрация',
            self::AUTH => 'Авторизация',
            self::UPDATE_PHONE => 'Обновление телефона',
            self::BUTTON_CLICK => 'Нажатие кнопки',
            self::START => 'Команда /start',
        };
    }
}
