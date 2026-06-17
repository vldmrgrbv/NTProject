<?php

if (! function_exists('formatScores')) {
    /**
     * Форматирует число с правильным склонением слова "балл"
     *
     * @param  int|string  $number
     */
    function formatScores($number): string
    {
        $number = (int) $number;

        // Получаем последние две цифры для исключений (11-14)
        $lastTwoDigits = $number % 100;

        // Если число от 11 до 14 - всегда "баллов"
        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 14) {
            return $number.' баллов';
        }

        // Получаем последнюю цифру
        $lastDigit = $number % 10;

        // Определяем окончание по последней цифре
        switch ($lastDigit) {
            case 1:
                return $number.' балл';
            case 2:
            case 3:
            case 4:
                return $number.' балла';
            default:
                return $number.' баллов';
        }
    }
}

if (! function_exists('formatWithLeadingZeros')) {
    /**
     * Форматирует число в формат XXXXXX, с нулями
     */
    function formatWithLeadingZeros($number, $length = 6)
    {
        return str_pad((string) $number, $length, '0', STR_PAD_LEFT);
    }
}

