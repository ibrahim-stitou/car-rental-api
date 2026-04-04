<?php

namespace App\Modules\User\Controllers;

use App\Core\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

class AuditController extends BaseController
{
    /**
     * @OA\Get(path="/logs", summary="Liste des logs d'audit", tags={"Logs"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $audits = Audit::with('user')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success($audits);
    }

    public function show(string $id): JsonResponse
    {
        $audit = Audit::with('user')->findOrFail($id);
        return $this->success($audit);
    }

    public function byModel(string $type, string $id): JsonResponse
    {
        $modelMap = [
            'agency'               => \App\Models\Agency::class,
            'vehicle'              => \App\Models\Vehicle::class,
            'client'               => \App\Models\Client::class,
            'reservation'          => \App\Models\Reservation::class,
            'maintenance'          => \App\Models\Maintenance::class,
            'insurance'            => \App\Models\Insurance::class,
            'vignette'             => \App\Models\Vignette::class,
            'technical-inspection' => \App\Models\TechnicalInspection::class,
            'user'                 => \App\Models\User::class,
        ];

        $modelClass = $modelMap[$type] ?? null;
        if (!$modelClass) {
            return $this->notFound('Invalid model type');
        }

        $audits = Audit::where('auditable_type', $modelClass)
            ->where('auditable_id', $id)
            ->with('user')
            ->latest()
            ->paginate(15);

        return $this->success($audits);
    }

    public function byUser(string $userId): JsonResponse
    {
        $audits = Audit::where('user_id', $userId)
            ->with('user')
            ->latest()
            ->paginate(15);

        return $this->success($audits);
    }

    public function destroy(string $id): JsonResponse
    {
        Audit::findOrFail($id)->delete();
        return $this->success(null, 'Audit log deleted');
    }
}

