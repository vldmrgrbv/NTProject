<?php

namespace App\Services;

use App\Enums\ReceiptSource;
use App\Enums\ReceiptStatus;
use App\Facades\Notify;
use App\Jobs\CheckNTIntegrationJob;
use App\Jobs\CheckBlacklistInnJob;
use App\Jobs\CheckDuplicateJob;
use App\Jobs\CheckFnsJob;
use App\Jobs\IdentifyReceiptProductsJob;
use App\Jobs\NotifyUserJob;
use App\Jobs\ProcessReceiptJob;
use App\Jobs\RegisterNTJob;
use App\Jobs\UpdateStatusCheckJob;
use App\Models\Receipt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;

class ReceiptService
{
    /**
     * Проверить лимиты пользователя.
     */
    public function checkLimits(User $user): bool
    {
        if ($user->is_whitelisted) {
            return true;
        }

        $todayCount = $user->receipts()
            ->where('status', '>=', 0)
            ->whereDate('created_at', Carbon::today())
            ->count();

        if ($todayCount >= config('nt.limits.receipts_daily', 2)) {
            Notify::setNotifyMessage($user, __('bot.notifications.limit_count'));
            return false;
        }

        $monthCount = $user->receipts()
            ->where('status', '>=', 0)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        if ($monthCount >= config('nt.limits.receipts_monthly', 4)) {
            Notify::setNotifyMessage($user, __('bot.notifications.limit_count'));
            return false;
        }

        return true;
    }

    /**
     * Создать временный чек.
     */
    public function createTemporaryReceipt(User $user, ReceiptSource $source = ReceiptSource::UPLOAD): Receipt
    {
        return $user->receipts()->create([
            'status' => ReceiptStatus::TEMPORARY,
            'source' => $source,
        ]);
    }

    /**
     * Добавить фото к чеку.
     */
    public function addPhoto(Receipt $receipt, string $path): void
    {
        $receipt->photos()->create([
            'path' => $path,
        ]);
    }

    /**
     * Поставить чек в очередь на обработку.
     */
    public function submitForProcessing(Receipt $receipt): void
    {
        $receipt->update(['status' => ReceiptStatus::PENDING]);

        // Если количество обрабатываемых чеков больше 4, задержка на 1 минуту
        $delay = Receipt::where('status', ReceiptStatus::PROCESSING)->count() >= 4 ? now()->addMinute() : now();

        Bus::chain([
            new ProcessReceiptJob($receipt),
            new CheckDuplicateJob($receipt),
            new CheckFnsJob($receipt),
            new CheckBlacklistInnJob($receipt),
            new RegisterNTJob($receipt),
            new IdentifyReceiptProductsJob($receipt),
            new UpdateStatusCheckJob($receipt),
            new CheckNTIntegrationJob($receipt),
            new NotifyUserJob($receipt),
        ])
            ->catch(function (\Throwable $e) use ($receipt) {
                // Если цепочка окончательно провалилась после всех ретраев
                $receipt->update([
                    'status' => ReceiptStatus::REJECTED,
                    'reason_failed' => 'Техническая ошибка после нескольких попыток: '.$e->getMessage(),
                ]);
                NotifyUserJob::dispatch($receipt);
            })
            ->delay($delay)
            ->dispatch();
    }

    /**
     * Распарсить строку QR-кода.
     */
    public function parseQrString(string $qrString): ?array
    {
        parse_str($qrString, $query);
        if (!isset($query['s']) || !isset($query['t']) || !isset($query['fn']) || !isset($query['i']) || !isset($query['fp'])) {
            return null;
        }

        return [
            'sum' => $query['s'] ?? null,
            'dt' => $this->parseQrDate($query['t'] ?? null),
            'fn' => $query['fn'] ?? null,
            'fd' => $query['i'] ?? null,
            'fp' => $query['fp'] ?? null,
        ];
    }

    protected function parseQrDate(?string $dateStr): ?string
    {
        if (! $dateStr) {
            return null;
        }

        try {
            // Формат может быть YYYYMMDDTHHMM или YYYYMMDDTHHMMSS
            if (strlen($dateStr) === 13) {
                return Carbon::createFromFormat('Ymd\THi', $dateStr)->toDateTimeString();
            }

            return Carbon::createFromFormat('Ymd\THis', $dateStr)->toDateTimeString();
        } catch (\Exception $e) {
            return null;
        }
    }
}
