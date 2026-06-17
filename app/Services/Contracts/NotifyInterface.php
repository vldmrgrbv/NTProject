<?php

namespace App\Services\Contracts;

use App\Models\User;

interface NotifyInterface
{
    public function setNotifyMessage(User $user, string $message): void;
    public function getNotifyMessage(User $user): string;
    public function resetNotifyMessage(User $user): void;

}
