<?php

namespace App\Services;

use App\Models\User;
use App\Services\Contracts\NotifyInterface;

class NotifyService implements NotifyInterface
{
    public function setNotifyMessage(User $user, string $message): void
    {
        $user->notify_message = $message;
        $user->save();
    }

    public function getNotifyMessage(User $user): string
    {
        return $user->notify_message;
    }

    public function resetNotifyMessage(User $user): void
    {
        $user->notify_message = null;
        $user->save();
    }
}
