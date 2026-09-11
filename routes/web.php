<?php

use Illuminate\Support\Facades\Route;

// Корень отдаёт SPA.
Route::view('/', 'app');

// Любой другой веб-адрес, который не подошёл к API/storage/health,
// тоже отдаёт SPA — дальше маршрутизацией занимается Vue Router в браузере.
Route::fallback(fn () => view('app'));
