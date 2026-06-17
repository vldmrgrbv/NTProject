<?php

namespace App\Services;

use App\Models\Receipt;
use App\Services\Contracts\ReceiptRecognitionServiceInterface;
use chillerlan\QRCode\Common\IMagickLuminanceSource;
use chillerlan\QRCode\QRCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class NTCheckService implements ReceiptRecognitionServiceInterface
{
    public function sendToRecognition(Receipt $receipt): array
    {
        $photo = $receipt->photos()->first();
        if (! $photo) {
            return ['status_code' => 404, 'message' => 'No photo found for receipt'];
        }

        try {
            $filePath = Storage::disk('s3')->path($photo->path);

            if (! file_exists($filePath)) {
                return ['status_code' => 404, 'message' => 'File not found on disk: '.$filePath];
            }

            // 1. Предобработка изображения
            $imagick = $this->preprocessImage($filePath);

            // 2. Поиск и декодирование QR-кода (умный поиск по фрагментам)
            $qrContent = $this->tryDecode($imagick);

            if (! $qrContent) {
                Log::warning("QR code not found on image for receipt {$receipt->id}");

                return ['status_code' => 404, 'message' => 'Receipt not found on Image'];
            }

            Log::info("QR code found for receipt {$receipt->id}: {$qrContent}");

            // 3. Парсинг параметров чека из QR-строки
            $receiptData = $this->parseQrString($qrContent);

            if (! $receiptData) {
                Log::warning("Invalid QR content for receipt {$receipt->id}: {$qrContent}");

                return ['status_code' => 404, 'message' => 'Invalid QR content'];
            }

            return [
                'status_code' => 200,
                'body' => $receiptData,
            ];

        } catch (\Exception $e) {
            Log::error("NTCheck sendToRecognition error for receipt {$receipt->id}: ".$e->getMessage());

            return ['status_code' => 500, 'message' => $e->getMessage()];
        }
    }

    /**
     * Предобработка изображения (grayscale + resize)
     */
    protected function preprocessImage(string $path): \Imagick
    {
        $imagick = new \Imagick($path);
        $imagick->modulateImage(100, 0, 100); // GrayScale

        $width = $imagick->getImageWidth();
        $height = $imagick->getImageHeight();

        // Уменьшаем масштаб (round(width / 1.5))
        $imagick->resizeImage((int) round($width / 1.5), (int) round($height / 1.5), \Imagick::FILTER_LANCZOS, 1);

        return $imagick;
    }

    /**
     * Попытка декодирования QR-кода с использованием "умного поиска" по фрагментам
     */
    protected function tryDecode(\Imagick $imagick): ?string
    {
        // 1. Сначала пробуем всё изображение
        $result = $this->decode($imagick);
        if ($result) {
            return $result;
        }

        $width = $imagick->getImageWidth();
        $height = $imagick->getImageHeight();

        // 2. Если не найден, ищем в правом нижнем углу
        for ($i = 0.1; $i < 1.0; $i += 0.1) {
            $fragment = clone $imagick;
            $w = (int) ($i * $width);
            $h = (int) ($i * $height);
            $fragment->cropImage($w, $h, $width - $w, $height - $h);

            $result = $this->decode($fragment);
            if ($result) {
                return $result;
            }
            $fragment->clear();
        }

        // 3. Если не найден, ищем в нижней части чека по центру
        for ($i = 0.1; $i < 1.0; $i += 0.1) {
            $fragment = clone $imagick;
            $w = (int) ($i * $width);
            $h = (int) ($i * $height);
            $x = (int) ($width * 0.5 - $w * 0.5);
            $fragment->cropImage($w, $h, max(0, $x), $height - $h);

            $result = $this->decode($fragment);
            if ($result) {
                return $result;
            }
            $fragment->clear();
        }

        return null;
    }

    /**
     * Декодирование фрагмента
     */
    protected function decode(\Imagick $imagick): ?string
    {
        try {
            $source = new IMagickLuminanceSource($imagick);
            $result = (new QRCode)->readFromSource($source);

            return (string) $result;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Парсинг строки QR-кода в массив данных чека
     */
    protected function parseQrString(string $qrString): ?array
    {
        // Извлекаем query часть если это URL
        $queryString = parse_url($qrString, PHP_URL_QUERY) ?: $qrString;
        parse_str($queryString, $query);

        // Проверка обязательных полей ФНС: s (сумма), t (время), fn (ФН), i (ФД), fp (ФП)
        if (! isset($query['s'], $query['t'], $query['fn'], $query['i'], $query['fp'])) {
            return null;
        }

        $dateStr = $query['t'];
        // Форматы времени ФНС: YmdTHi или YmdTHis
        $format = strlen($dateStr) === 13 ? 'Ymd\THi' : 'Ymd\THis';
        $date = \DateTime::createFromFormat($format, $dateStr);

        return [
            'sum' => $query['s'],
            'date' => $date ? $date->format('Y-m-d H:i:s') : now()->toDateTimeString(),
            'fn' => $query['fn'],
            'fd' => $query['i'],
            'fp' => $query['fp'],
        ];
    }
}
