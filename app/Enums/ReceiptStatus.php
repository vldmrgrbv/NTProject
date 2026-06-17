<?php

declare(strict_types=1);

namespace App\Enums;

enum ReceiptStatus: int
{
    case TEMPORARY = -100;
    case PENDING = 0;
    case PROCESSING = 10;
    case ACCEPTED = 100;
    case REJECTED = -1;

    public function toString(): ?string
    {
        return match ($this) {
            self::TEMPORARY => 'Временный',
            self::PENDING => 'В очереди',
            self::PROCESSING => 'В обработке',
            self::ACCEPTED => 'Принят',
            self::REJECTED => 'Отклонен',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::TEMPORARY => 'warning',
            self::PENDING => 'primary',
            self::PROCESSING => 'info',
            self::ACCEPTED => 'success',
            self::REJECTED => 'error',
        };
    }
}
