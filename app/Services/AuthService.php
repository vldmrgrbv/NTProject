<?php

namespace App\Services;

use App\Models\MaxUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AuthService
{
    public function updateOrCreateUser(Request $request, array $userData): User
    {
        $phone = $request->phone;
        $maxUser = $request->input('max_user');

        $user = User::updateOrCreate(
            ['phone' => $phone],
            $userData
        );

        if ($maxUser) {
            MaxUser::updateOrCreate(
                [
                    'max_id' => data_get($maxUser, 'id'),
                ],
                [
                    'user_id' => $user->id,
                    'first_name' => data_get($maxUser, 'first_name'),
                    'second_name' => data_get($maxUser, 'last_name'),
                    'username' => data_get($maxUser, 'username'),
                ]
            );
        }

        return $user;
    }

    public function checkAuthCodeAttempts(User $user): bool
    {
        if ($user->auth_code_attempts >= config('nt.limits.auth_code_attempts')) {
            if (Carbon::now()->diffInMinutes($user->updated_at, true) < config('nt.limits.auth_code_attempts_reset_minutes')) {
                return false;
            }
            $user->auth_code_attempts = 0;
            $user->save();
        }

        return true;
    }

    public function resetAuthCodeAttempts(User $user): void
    {
        $user->auth_code_attempts = 0;
        $user->save();
    }

    public function incrementAuthCodeAttempts(User $user): void
    {
        $user->auth_code_attempts++;
        $user->save();
    }
}
