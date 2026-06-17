<?php

declare(strict_types=1);

namespace App\Enums;

enum ReceiptSource: string
{
    case MANUAL = 'manual';
    case QR = 'qr';
    case UPLOAD = 'upload';

    public function toString(): string
    {
        return match ($this) {
            self::MANUAL => 'Ручной',
            self::QR => 'QR-код',
            self::UPLOAD => 'Загрузка фото',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MANUAL => 'info',
            self::QR => 'primary',
            self::UPLOAD => 'success',
        };
    }
}
