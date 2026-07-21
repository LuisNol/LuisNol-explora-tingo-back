<?php
  
namespace App\Http\Controllers;
  
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
// use Validator;
  
  
class AuthController extends Controller
{
 
    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register() {
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        if($validator->fails()){
            $errors = $validator->errors()->toArray();
            if (isset($errors['email'])) {
                return response()->json([
                    'message' => 'Ya tiene un usuario registrado con este datos',
                    'errors' => $errors,
                ], 409);
            }
            return response()->json([
                'message' => 'Error en los datos enviados',
                'errors' => $errors,
            ], 400);
        }

        $user = new User;
        $user->name = request()->name;
        $user->email = request()->email;
        $user->password = request()->password;
        $user->type_user = request()->type_user ?? 1;
        $user->save();

        return response()->json([
            'message' => 'Usuario registrado correctamente',
            'user' => $user,
        ], 201);
    }
  
  
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);
  
        if (! $token = auth("api")->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
  
        return $this->respondWithToken($token);
    }
  
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth("api")->user());
    }
  
    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth("api")->logout();
  
        return response()->json(['message' => 'Successfully logged out']);
    }
  
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth("api")->refresh());
    }
  
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $user = auth("api")->user();
        $permissions = $user->getAllPermissions()->map(function($permission) {
            return $permission->name;
        });
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth("api")->factory()->getTTL() * 60,
            "user" => [
                "full_name" => $user->name,
                "email" => $user->email,
                "avatar" => $user->avatar ? Storage::disk('public')->url($user->avatar) : null,
                "role" => $user->role_id ? [
                    "id" => $user->role->id,
                    "name" => $user->role->name,
                ] : null,
                "permissions" => $permissions,
                "type_user" => $user->type_user,
            ],
        ]);
    }
}