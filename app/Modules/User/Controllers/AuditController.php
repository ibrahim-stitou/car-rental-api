<?php

namespace App\Modules\User\Controllers;

use App\Core\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

class AuditController extends BaseController
{
    /**
     * Short resource key => FQCN, shared between the resource-type filter and byModel().
     */
    public const MODEL_MAP = [
        'agency'               => \App\Models\Agency::class,
        'vehicle'              => \App\Models\Vehicle::class,
        'client'                => \App\Models\Client::class,
        'reservation'          => \App\Models\Reservation::class,
        'reservation-payment'  => \App\Models\ReservationPayment::class,
        'maintenance'          => \App\Models\Maintenance::class,
        'insurance'            => \App\Models\Insurance::class,
        'vignette'             => \App\Models\Vignette::class,
        'technical-inspection' => \App\Models\TechnicalInspection::class,
        'claim'                => \App\Models\Claim::class,
        'expense'              => \App\Models\Expense::class,
        'billing-document'     => \App\Models\BillingDocument::class,
        'parameter'            => \App\Models\Parameter::class,
        'user'                 => \App\Models\User::class,
    ];

    /**
     * @OA\Get(path="/logs", summary="Liste des logs d'audit", tags={"Logs"}, security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *   @OA\Parameter(name="auditable_type", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="user_id", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="date_from", in="query", @OA\Schema(type="string", format="date")),
     *   @OA\Parameter(name="date_to", in="query", @OA\Schema(type="string", format="date")),
     *   @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Audit::with('user')->latest();

        if ($request->filled('auditable_type')) {
            $modelClass = self::MODEL_MAP[$request->string('auditable_type')->toString()] ?? null;
            if ($modelClass) {
                $query->where('auditable_type', $modelClass);
            }
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $audits = $query->paginate($request->integer('per_page', 15));

        return $this->paginated($audits, null);
    }

    public function show(string $id): JsonResponse
    {
        $audit = Audit::with('user')->findOrFail($id);
        return $this->success($audit);
    }

    public function byModel(string $type, string $id): JsonResponse
    {
        $modelClass = self::MODEL_MAP[$type] ?? null;
        if (!$modelClass) {
            return $this->notFound('Invalid model type');
        }

        $audits = Audit::where('auditable_type', $modelClass)
            ->where('auditable_id', $id)
            ->with('user')
            ->latest()
            ->paginate(15);

        return $this->paginated($audits, null);
    }

    public function byUser(string $userId): JsonResponse
    {
        $audits = Audit::where('user_id', $userId)
            ->with('user')
            ->latest()
            ->paginate(15);

        return $this->paginated($audits, null);
    }

    public function destroy(string $id): JsonResponse
    {
        Audit::findOrFail($id)->delete();
        return $this->success(null, 'Audit log deleted');
    }
}

