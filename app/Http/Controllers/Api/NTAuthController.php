<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterUserRequest;
use App\Http\Requests\Api\SendCodeRequest;
use App\Http\Requests\Api\VerifyCodeRequest;
use App\Models\MaxUser;
use App\Models\User;
use App\Models\UserEvent;
use App\Services\NTApiService;
use App\Services\AuthService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class NTAuthController extends Controller
{
    public function __construct(
        protected NTApiService $ntApi,
        protected AuthService $authService,
    ) {}

    /**
     * Шаг 1: Проверка пользователя и отправка SMS-кода
     */
    public function sendCode(SendCodeRequest $request): JsonResponse
    {
        $phone = $request->phone;

        $sendResponse = $this->ntApi->sendCode($phone);
        if (isset($sendResponse['status']) && $sendResponse['status'] === 'success') {
            $userData = [
                'auth_code' => $sendResponse['data']['code'] ?? null,
                'is_authorized' => false,
            ];
            $user = $this->authService->updateOrCreateUser($request, $userData);
            $this->authService->resetAuthCodeAttempts($user);
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Код отправлен (авторизация)',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Ошибка при обработке запроса',
            'details' => $sendResponse ?? null,
        ], 400);
    }

    /**
     * Шаг 2: Проверка кода и авторизация
     */
    public function verifyCode(VerifyCodeRequest $request): JsonResponse
    {
        $phone = $request->phone;
        $code = $request->code;
        $user = User::where('phone', $phone)->first();
        $user?->checkAuthCode();

        if ($user && is_null($user->auth_code)) {
            return response()->json([
                'success' => false,
                'message' => 'Проверочный код не найден. Попробуйте отправить его еще раз.',
            ]);
        }

        if (! $this->authService->checkAuthCodeAttempts($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Превышено количество попыток ввода проверочного кода. Попробуйте позже.',
            ], 403);
        }

        if ($user && $user->auth_code === $code) {
            $checkResponse = $this->ntApi->checkUser($phone);
            if (isset($checkResponse['status']) && $checkResponse['status'] === 'error') {
                return response()->json([
                    'success' => false,
                    'message' => 'К сожалению, мне не удалось ничего найти. Ты уверен, что номер правильный? Попробуй ввести его еще раз. Лучше всего делать это в формате: 79ХХХХХХХХХ.',
                ], 403);
            }

            $authResponse = $this->ntApi->auth($phone);
            if (isset($authResponse['status']) && $authResponse['status'] === 'success') {
                $user->update([
                    'is_authorized' => true,
                    'auth_code' => null,
                    'auth_code_attempts' => 0,
                    'external_id' => $checkResponse['data']['user_id'] ?? null,
                ]);

                $token = $user->new_token;

                $this->ntApi->integrationAuthSend($phone);

                // В БД пишется событие auth
                UserEvent::create([
                    'user_id' => $user->id,
                    'event_type' => UserEventType::AUTH,
                    'payload' => $authResponse['data'] ?? [],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Авторизация успешна',
                    'token' => $token,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка авторизации',
                ], 401);
            }
        }

        $this->authService->incrementAuthCodeAttempts($user);
        return response()->json([
            'success' => false,
            'message' => 'Неверный код',
        ], 400);
    }

    /**
     * Регистрация нового пользователя
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $phone = $request->phone;
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'birthday' => Carbon::parse($request->birthday),
            'gender' => $request->gender,
            'password' => $request->password,
            'marketing_agree' => $request->marketing_agree,
            'privacy_agree' => $request->privacy_agree,
        ];

        $checkResponse = $this->ntApi->checkUser($phone);
        if (isset($checkResponse['status']) && $checkResponse['status'] === 'success') {
            $this->authService->updateOrCreateUser($request, $userData);
            return response()->json([
                'success' => false,
                'message' => 'Пользователь с таким телефоном уже зарегистрирован',
            ], 400);
        }

        $response = $this->ntApi->registerUser($request->validated());
        if (isset($response['status']) && $response['status'] === 'success') {
            $userData['external_id'] = $response['data']['user_id'] ?? null;
            $user = $this->authService->updateOrCreateUser($request, $userData);

            UserEvent::create([
                'user_id' => $user->id,
                'event_type' => UserEventType::REG,
                'payload' => $response['data'] ?? [],
            ]);

            // При успешной регистрации выполняется auth(phone)
            $authResponse = $this->ntApi->auth($phone);
            if (isset($authResponse['status']) && $authResponse['status'] === 'error') {
                $user->update(['is_authorized' => false]);

                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при аутентификации',
                    'details' => $authResponse,
                ], 500);
            }

            $token = $user->new_token;
            $user->update([
                'is_authorized' => true,
                'auth_code' => null,
                'auth_code_attempts' => 0,
            ]);

            UserEvent::create([
                'user_id' => $user->id,
                'event_type' => UserEventType::AUTH,
                'payload' => $authResponse['data'] ?? [],
            ]);

            $this->ntApi->integrationRegSend($phone);

            return response()->json([
                'success' => true,
                'message' => 'Пользователь успешно зарегистрирован и авторизован',
                'token' => $token,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'При регистрации произошла ошибка. Попробуй позднее.',
            'details' => $response,
        ], 400);
    }
}
