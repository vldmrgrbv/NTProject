<?php

declare(strict_types=1);

namespace App\Enums;

enum NTCheckStatus: string
{
    case NEW = 'new';
    case PROCESSED = 'processed';
    case ERROR = 'error';

    public function toString(): string
    {
        return match ($this) {
            self::NEW => 'Новый',
            self::PROCESSED => 'Обработан',
            self::ERROR => 'Ошибка',
        };
    }
}
