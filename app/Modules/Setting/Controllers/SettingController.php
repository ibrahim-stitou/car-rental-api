<?php

namespace App\Modules\Setting\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\Setting\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends BaseController
{
    public function __construct(protected SettingService $service) {}

    /**
     * @OA\Get(path="/settings", summary="Tous les paramètres regroupés", tags={"Settings"}, security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function index(): JsonResponse
    {
        return $this->success($this->service->getAll());
    }

    /**
     * @OA\Get(path="/settings/{group}", summary="Paramètres d'un groupe", tags={"Settings"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="group", in="path", required=true, @OA\Schema(type="string",
     *     enum={"company","billing","notifications","reservation","website"})),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function group(string $group): JsonResponse
    {
        return $this->success($this->service->getGroup($group));
    }

    /**
     * @OA\Put(path="/settings/{group}", summary="Mettre à jour un groupe de paramètres", tags={"Settings"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="group", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\RequestBody(required=true, @OA\JsonContent()),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(Request $request, string $group): JsonResponse
    {
        $updated = $this->service->updateGroup($group, $request->all());
        return $this->success($updated, 'Paramètres mis à jour');
    }

    /**
     * @OA\Post(path="/settings/logo", summary="Uploader le logo de la société", tags={"Settings"}, security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="logo", type="string", format="binary"))
     *   )),
     *   @OA\Response(response=200, description="Logo uploaded")
     * )
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate(['logo' => 'required|image|mimes:jpeg,png,webp,svg|max:2048']);
        $url = $this->service->uploadLogo($request->file('logo'));
        return $this->success(['url' => $url], 'Logo uploadé');
    }

    /**
     * @OA\Post(path="/settings/hero-image", summary="Uploader l'image hero du site", tags={"Settings"}, security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(@OA\Property(property="image", type="string", format="binary"))
     *   )),
     *   @OA\Response(response=200, description="Image uploaded")
     * )
     */
    public function uploadHeroImage(Request $request): JsonResponse
    {
        $request->validate(['image' => 'required|image|mimes:jpeg,png,webp|max:5120']);
        $url = $this->service->uploadHeroImage($request->file('image'));
        return $this->success(['url' => $url], 'Image hero uploadée');
    }
}
