<?php

namespace App\Http\Controllers\Api;

use App\Core\Http\Controllers\BaseController;
use App\Services\TempFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class FileController extends BaseController
{
    public function __construct(protected TempFileService $tempFileService) {}

    /**
     * @OA\Post(path="/files/upload-temp", summary="Upload d'un fichier temporaire", tags={"Files"}, security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(
     *       required={"file"},
     *       @OA\Property(property="file", type="string", format="binary")
     *     )
     *   )),
     *   @OA\Response(response=200, description="File uploaded", @OA\JsonContent(
     *     @OA\Property(property="id", type="string", format="uuid"),
     *     @OA\Property(property="original_name", type="string"),
     *     @OA\Property(property="mime_type", type="string"),
     *     @OA\Property(property="size", type="integer"),
     *     @OA\Property(property="preview_url", type="string")
     *   ))
     * )
     */
    public function uploadTemp(Request $request): JsonResponse
    {
        $request->validate([
            'file'     => 'required|file|max:51200',
        ]);

        $result = $this->tempFileService->store($request->file('file'));

        return $this->success($result, 'File uploaded temporarily');
    }

    /**
     * @OA\Post(path="/files/upload-temp-multiple", summary="Upload de plusieurs fichiers temporaires", tags={"Files"}, security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="multipart/form-data",
     *     @OA\Schema(
     *       required={"files"},
     *       @OA\Property(property="files[]", type="array", @OA\Items(type="string", format="binary"))
     *     )
     *   )),
     *   @OA\Response(response=200, description="Files uploaded")
     * )
     */
    public function uploadTempMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'files'   => 'required|array|max:20',
            'files.*' => 'file|max:51200',
        ]);

        $results = $this->tempFileService->storeMany($request->file('files'));

        return $this->success($results, 'Files uploaded temporarily');
    }

    /**
     * @OA\Post(path="/files/cleanup-temp", summary="Supprimer des fichiers temporaires", tags={"Files"}, security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"ids"},
     *     @OA\Property(property="ids", type="array", @OA\Items(type="string", format="uuid"))
     *   )),
     *   @OA\Response(response=200, description="Files deleted")
     * )
     */
    public function cleanupTemp(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'string|uuid',
        ]);

        $this->tempFileService->deleteMany($request->input('ids'));

        return $this->success(null, 'Temporary files cleaned up');
    }

    /**
     * @OA\Get(path="/files/temp-preview/{id}", summary="Prévisualiser un fichier temporaire", tags={"Files"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *   @OA\Response(response=200, description="File content"),
     *   @OA\Response(response=404, description="File not found")
     * )
     */
    public function previewTemp(string $id): Response
    {
        $path = $this->tempFileService->getAbsolutePath($id);

        abort_if(! $path || ! file_exists($path), 404, 'Temporary file not found');

        $mime     = mime_content_type($path);
        $content  = file_get_contents($path);

        return response($content, 200, [
            'Content-Type'        => $mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }
}
