<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\Factory as Socialite;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use GuzzleHttp\Client;

class AuthController extends Controller
{
    /**
     * Registrar un nuevo Usuario.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register()
    {
        $validated = $this->validateRegistrationRequest();
        
        $user = $this->createUser($validated);
        
        return $this->formatSuccessResponse($user, 'Usuario registrado correctamente', 201);
    }

    /**
     * Autenticar Usuario y devolver token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = $this->validateLoginRequest();
        
        try {
            if (!$token = auth("api")->attempt($credentials)) {
                throw ValidationException::withMessages([
                    'email' => ['Credenciales inválidas']
                ])->status(401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'No se pudo generar el token',
                'details' => $e->getMessage()
            ], 500);
        }
        
        return $this->respondWithToken($token);
    }

    /**
     * Obtener el Usuario autenticado.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = auth("api")->user();
        return $this->formatUserResponse($user);
    }

    /**
     * Cerrar sesión (Invalidar el token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth("api")->logout();
        return response()->json(['message' => 'Successfully logged out'], 200);
    }

    /**
     * Actualizar token de acceso.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        try {
            return $this->respondWithToken(auth("api")->refresh());
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'No se pudo refrescar el token',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Redirigir al proveedor OAuth de Google.
     */
    public function redirectToGoogle()
    {
        $socialite = app(Socialite::class)->driver('google')->stateless();

        if (app()->environment('local')) {
            $socialite->setHttpClient(new Client(['verify' => false]));
        }

        return $socialite
            ->with(['prompt' => 'consent'])
            ->redirect();
    }

    /**
     * Obtener usuario de Google y autenticar.
     */
    public function handleGoogleCallback()
    {
        $payload = null;
        $statusCode = 200;

        try {
            $socialite = app(Socialite::class)->driver('google')->stateless();

            if (app()->environment('local')) {
                $socialite->setHttpClient(new Client(['verify' => false]));
            }

            $googleUser = $socialite->user();
        } catch (Exception $e) {
            $payload = [
                'error' => 'Autenticación con Google fallida',
                'details' => $e->getMessage(),
            ];
            $statusCode = 400;
        }

        if (!$payload) {
            $user = $this->findOrCreateGoogleUser($googleUser);

            try {
                $token = auth("api")->login($user);
            } catch (JWTException $e) {
                $payload = [
                    'error' => 'No se pudo generar el token JWT',
                    'details' => $e->getMessage(),
                ];
                $statusCode = 500;
            }

            if (!$payload) {
                $payload = [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => auth("api")->factory()->getTTL() * 60,
                    'user' => $this->formatUserResponse($user),
                ];
            }
        }

        return $this->googleCallbackHtml($payload, $statusCode);
    }

    /**
     * Generar respuesta HTML para callback de Google en redirección SPA.
     *
     * @param  array  $payload
     * @param  int   $statusCode
     * @return \Illuminate\Http\Response
     */
    private function googleCallbackHtml(array $payload, int $statusCode)
    {
        $frontendUrl = rtrim((string) env('GOOGLE_FRONTEND_URL', config('app.url')), '/');
        $callbackUrl = $frontendUrl ? $frontendUrl . '/auth/google/callback' : '/auth/google/callback';

        $json = json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $encoded = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $callbackUrlJs = addslashes($callbackUrl . '#payload=' . $encoded);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autenticando con Google...</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f5f5f5; }
        .message { text-align: center; color: #333; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #000; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="message">
        <div class="spinner"></div>
        <p>Autenticando con Google...</p>
    </div>
    <script>
        (function() {
            window.location.href = "{$callbackUrlJs}";
        })();
    </script>
</body>
</html>
HTML;

        return response($html, $statusCode)->header('Content-Type', 'text/html');
    }

    /**
     * Obtener estructura del token.
     *
     * @param  string $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $user = auth("api")->user();
        $permissions = $user->getAllPermissions()->map(function ($permission) {
            return $permission->name;
        });
        
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth("api")->factory()->getTTL() * 60,
            'user' => $this->formatUserResponse($user)
        ]);
    }

    /**
     * Validar y devolver datos de registro.
     *
     * @return array
     */
    private function validateRegistrationRequest()
    {
        return Validator::validate(request()->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'type_user' => ['sometimes', 'integer', 'in:1,2'], // 1 = ecommerce, 2 = admin
        ]);
    }

    /**
     * Validar y devolver credenciales de login.
     *
     * @return array
     */
    private function validateLoginRequest()
    {
        return Validator::validate(request()->only(['email', 'password']), [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);
    }

    /**
     * Crear nuevo usuario.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    private function createUser(array $data)
    {
        return User::create(array_merge($data, [
            'type_user' => $data['type_user'] ?? 1,
            'password' => bcrypt($data['password']),
        ]));
    }

    /**
     * Encontrar usuario existente o crear nuevo usuario de Google.
     *
     * @param  \Laravel\Socialite\Two\GoogleUser $googleUser
     * @return \App\Models\User
     */
    private function findOrCreateGoogleUser($googleUser)
    {
        $existingUser = User::where('email', $googleUser->getEmail())->first();

        if ($existingUser) {
            if (!$existingUser->google_id) {
                $existingUser->google_id = $googleUser->getId();
            }

            if (!$existingUser->avatar && $googleUser->getAvatar()) {
                $existingUser->avatar = $googleUser->getAvatar();
            }

            $existingUser->save();

            return $existingUser;
        }

        return User::create([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'type_user' => 1,
            'password' => bcrypt(random_bytes(16)),
        ]);
    }

    /**
     * Formatear datos de usuario para respuesta API.
     *
     * @param  \App\Models\User $user
     * @return array
     */
    private function formatUserResponse(User $user)
    {
        $permissions = $user->getAllPermissions()->map(function ($permission) {
            return $permission->name;
        });

        $avatar = $user->avatar;

        if ($avatar && !preg_match('#^https?://#', $avatar)) {
            $avatar = Storage::disk('public')->url($avatar);
        }

        return [
            'full_name' => $user->name,
            'email' => $user->email,
            'avatar' => $avatar,
            'role' => $user->role_id ? [
                'id' => $user->role->id,
                'name' => $user->role->name,
            ] : null,
            'permissions' => $permissions,
            'type_user' => $user->type_user,
            'id' => $user->id,
            'name' => $user->name,
            'google_id' => $user->google_id,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    /**
     * Formatear respuesta API.
     *
     * @param  mixed  $data
     * @param  string $message
     * @param  int    $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    private function formatSuccessResponse($data, string $message, int $statusCode)
    {
        return response()->json([
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
}