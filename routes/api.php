<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Публичные роуты (вход доступен всем — иначе как войти).
Route::post('/login', [AuthController::class, 'login']);

// Защищённые роуты: попасть сюда можно только с валидной сессией (auth:sanctum).
// Если куки нет/протухла — Laravel вернёт 401 Unauthorized.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
