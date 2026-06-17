<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class TokenService
{
    public function encrypt(array $parameters): string
    {
        return Crypt::encryptString(json_encode($parameters));
    }

    public function decrypt(string $token): array
    {
        return json_decode(Crypt::decryptString($token), true);
    }
}
