<?php

use App\Http\Controllers\MaxBotRedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/max-bot/redirect/{token}', MaxBotRedirectController::class);
