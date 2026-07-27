<?php

namespace App\Modules\User\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends BaseController
{
    /**
     * @OA\Post(
     *   path="/auth/login",
     *   summary="Connexion utilisateur",
     *   tags={"Auth"},
     *   @OA\RequestBody(required=true,
     *     @OA\JsonContent(
     *       required={"email","password"},
     *       @OA\Property(property="email", type="string", format="email"),
     *       @OA\Property(property="password", type="string", format="password")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Login successful"),
     *   @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return $this->unauthorized('Invalid email or password');
        }

        $user = auth('api')->user();

        if (!$user->is_active) {
            auth('api')->logout();
            return $this->error('Votre compte a été suspendu. Veuillez contacter l\'administrateur.', 403);
        }

        $user->update(['last_login_at' => now()]);

        return $this->respondWithToken($token, $user);
    }

    /**
     * @OA\Post(
     *   path="/auth/register",
     *   summary="Inscription utilisateur",
     *   tags={"Auth"},
     *   @OA\RequestBody(required=true,
     *     @OA\JsonContent(
     *       required={"first_name","last_name","email","password","password_confirmation"},
     *       @OA\Property(property="first_name", type="string"),
     *       @OA\Property(property="last_name", type="string"),
     *       @OA\Property(property="email", type="string", format="email"),
     *       @OA\Property(property="password", type="string", format="password"),
     *       @OA\Property(property="password_confirmation", type="string")
     *     )
     *   ),
     *   @OA\Response(response=201, description="User registered"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name'            => 'required|string|max:255',
            'last_name'             => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
            'phone'                 => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'email'      => $request->email,
            'password'   => $request->password,
            'phone'      => $request->phone,
        ]);

        $user->assignRole(Role::findByName('viewer', 'api'));

        $token = auth('api')->login($user);

        return $this->created($this->tokenData($token, $user), 'User registered successfully');
    }

    /**
     * @OA\Post(
     *   path="/auth/logout",
     *   summary="Déconnexion",
     *   tags={"Auth"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Logged out")
     * )
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();
        return $this->success(null, 'Successfully logged out');
    }

    /**
     * @OA\Post(
     *   path="/auth/refresh",
     *   summary="Rafraîchir le token JWT",
     *   tags={"Auth"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Token refreshed")
     * )
     */
    public function refresh(): JsonResponse
    {
        $token = auth('api')->refresh();
        $user = auth('api')->user();
        return $this->respondWithToken($token, $user);
    }

    /**
     * @OA\Get(
     *   path="/auth/me",
     *   summary="Infos utilisateur connecté",
     *   tags={"Auth"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="User info")
     * )
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        $user->load('agencies');

        return $this->success([
            'id'          => $user->id,
            'first_name'  => $user->first_name,
            'last_name'   => $user->last_name,
            'full_name'   => $user->full_name,
            'email'       => $user->email,
            'phone'       => $user->phone,
            'is_active'   => $user->is_active,
            'agencies'    => $user->agencies,
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'avatar'      => $user->getFirstMediaUrl('avatar'),
        ]);
    }

    /**
     * @OA\Post(
     *   path="/auth/forgot-password",
     *   summary="Demande de réinitialisation de mot de passe",
     *   tags={"Auth"},
     *   @OA\RequestBody(required=true,
     *     @OA\JsonContent(required={"email"}, @OA\Property(property="email", type="string", format="email"))
     *   ),
     *   @OA\Response(response=200, description="Reset link sent")
     * )
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $status = Password::broker()->sendResetLink(['email' => $request->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return $this->success(null, 'Password reset link sent to your email');
        }

        return $this->error(__($status), 422);
    }

    /**
     * @OA\Post(
     *   path="/auth/reset-password",
     *   summary="Réinitialisation de mot de passe",
     *   tags={"Auth"},
     *   @OA\RequestBody(required=true,
     *     @OA\JsonContent(
     *       required={"email","token","password","password_confirmation"},
     *       @OA\Property(property="email", type="string"),
     *       @OA\Property(property="token", type="string"),
     *       @OA\Property(property="password", type="string"),
     *       @OA\Property(property="password_confirmation", type="string")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Password reset")
     * )
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|exists:users,email',
            'token'    => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->update(['password' => $password]);
                $user->setRememberToken(\Illuminate\Support\Str::random(60));
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success(null, 'Password reset successfully');
        }

        return $this->error(__($status), 422);
    }

    protected function respondWithToken(string $token, $user): JsonResponse
    {
        return $this->success($this->tokenData($token, $user));
    }

    protected function tokenData(string $token, $user): array
    {
        $user->loadMissing('agencies');

        return [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'user'         => [
                'id'          => $user->id,
                'first_name'  => $user->first_name,
                'last_name'   => $user->last_name,
                'full_name'   => $user->full_name,
                'email'       => $user->email,
                'roles'       => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'agencies'    => $user->agencies,
            ],
        ];
    }
}
