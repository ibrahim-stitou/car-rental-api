<?php

namespace App\Modules\User\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\User\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends BaseController
{
    /**
     * @OA\Get(path="/profile", summary="Profil utilisateur connecté", tags={"Profile"}, security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function show(): JsonResponse
    {
        $user = auth('api')->user();
        $user->load(['agency', 'roles', 'permissions']);
        return $this->success(new UserResource($user));
    }

    /**
     * @OA\Put(path="/profile", summary="Modifier le profil", tags={"Profile"}, security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     @OA\Property(property="first_name", type="string"),
     *     @OA\Property(property="last_name", type="string"),
     *     @OA\Property(property="phone", type="string")
     *   )),
     *   @OA\Response(response=200, description="Profile updated")
     * )
     */
    public function update(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $data = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name'  => 'sometimes|string|max:255',
            'phone'      => 'nullable|string|max:20',
            'email'      => 'sometimes|email|unique:users,email,' . $user->id,
        ]);

        $user->update($data);
        return $this->success(new UserResource($user->fresh()), 'Profile updated');
    }

    /**
     * @OA\Put(path="/profile/password", summary="Changer le mot de passe", tags={"Profile"}, security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"current_password","password","password_confirmation"},
     *     @OA\Property(property="current_password", type="string"),
     *     @OA\Property(property="password", type="string"),
     *     @OA\Property(property="password_confirmation", type="string")
     *   )),
     *   @OA\Response(response=200, description="Password changed")
     * )
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = auth('api')->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect', 422);
        }

        $user->update(['password' => $request->password]);
        return $this->success(null, 'Password changed successfully');
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate(['avatar' => 'required|image|mimes:jpeg,png,webp|max:2048']);
        $user = auth('api')->user();
        $user->addMedia($request->file('avatar'))->toMediaCollection('avatar');
        return $this->success([
            'url'   => $user->getFirstMediaUrl('avatar'),
            'thumb' => $user->getFirstMediaUrl('avatar', 'thumb'),
        ], 'Avatar uploaded');
    }
}

