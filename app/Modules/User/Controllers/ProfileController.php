<?php

namespace App\Modules\User\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\User\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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
        $user->load(['agencies', 'roles', 'permissions']);
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

    public function uploadSignature(Request $request): JsonResponse
    {
        $request->validate([
            'signature' => 'required|image|mimes:jpeg,png,webp|max:2048',
            'agency_id' => 'required|uuid|exists:agencies,id',
        ]);
        $user = auth('api')->user();
        $membership = $this->requireAgencyMembership($user, $request->agency_id);
        if ($membership instanceof JsonResponse) {
            return $membership;
        }

        if ($membership->pivot->signature_path) {
            Storage::disk('public')->delete($membership->pivot->signature_path);
        }
        $path = $request->file('signature')->store('agency-signatures', 'public');
        $user->agencies()->updateExistingPivot($request->agency_id, ['signature_path' => $path]);

        return $this->success(['url' => Storage::disk('public')->url($path)], 'Signature enregistrée');
    }

    public function uploadStamp(Request $request): JsonResponse
    {
        $request->validate([
            'stamp'     => 'required|image|mimes:jpeg,png,webp|max:2048',
            'agency_id' => 'required|uuid|exists:agencies,id',
        ]);
        $user = auth('api')->user();
        $membership = $this->requireAgencyMembership($user, $request->agency_id);
        if ($membership instanceof JsonResponse) {
            return $membership;
        }

        if ($membership->pivot->stamp_path) {
            Storage::disk('public')->delete($membership->pivot->stamp_path);
        }
        $path = $request->file('stamp')->store('agency-stamps', 'public');
        $user->agencies()->updateExistingPivot($request->agency_id, ['stamp_path' => $path]);

        return $this->success(['url' => Storage::disk('public')->url($path)], 'Cachet enregistré');
    }

    public function deleteSignature(Request $request): JsonResponse
    {
        $request->validate(['agency_id' => 'required|uuid|exists:agencies,id']);
        $user = auth('api')->user();
        $membership = $this->requireAgencyMembership($user, $request->agency_id);
        if ($membership instanceof JsonResponse) {
            return $membership;
        }

        if ($membership->pivot->signature_path) {
            Storage::disk('public')->delete($membership->pivot->signature_path);
        }
        $user->agencies()->updateExistingPivot($request->agency_id, ['signature_path' => null]);

        return $this->success(null, 'Signature supprimée');
    }

    public function deleteStamp(Request $request): JsonResponse
    {
        $request->validate(['agency_id' => 'required|uuid|exists:agencies,id']);
        $user = auth('api')->user();
        $membership = $this->requireAgencyMembership($user, $request->agency_id);
        if ($membership instanceof JsonResponse) {
            return $membership;
        }

        if ($membership->pivot->stamp_path) {
            Storage::disk('public')->delete($membership->pivot->stamp_path);
        }
        $user->agencies()->updateExistingPivot($request->agency_id, ['stamp_path' => null]);

        return $this->success(null, 'Cachet supprimé');
    }

    /**
     * Returns the matching agency (with its pivot loaded) if the user belongs
     * to it, or a 403 JsonResponse otherwise — cachets are strictly per
     * (user, agency), so uploading one requires actual membership.
     */
    private function requireAgencyMembership($user, string $agencyId)
    {
        $agency = $user->agencies()->where('agencies.id', $agencyId)->first();
        if (!$agency) {
            return $this->forbidden("Vous n'appartenez pas à cette agence");
        }
        return $agency;
    }
}

