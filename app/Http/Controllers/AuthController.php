<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Вход: шаг 2 нашей схемы.
     * Проверяем email+пароль, при успехе создаём сессию (кука уходит в браузер).
     */
    public function login(Request $request): JsonResponse
    {
        // Валидация: без этих полей дальше не идём.
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Auth::attempt сам найдёт юзера по email и сверит хеш пароля.
        if (! Auth::attempt($credentials)) {
            // Специально НЕ уточняем, что именно неверно (email или пароль) —
            // это защита: злоумышленник не должен узнать, какие email существуют.
            throw ValidationException::withMessages([
                'email' => 'Неверный email или пароль.',
            ]);
        }

        // Меняем id сессии после входа — защита от session fixation атаки.
        $request->session()->regenerate();

        return response()->json([
            'user' => Auth::user(),
        ]);
    }

    /**
     * Текущий пользователь: шаг 3. Доступен только залогиненным (см. роут).
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Выход: шаг 4. Убиваем сессию, чтобы кука стала бесполезной.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();      // уничтожаем данные сессии
        $request->session()->regenerateToken(); // новый CSRF-токен

        return response()->json(['message' => 'Вы вышли из системы.']);
    }
}
