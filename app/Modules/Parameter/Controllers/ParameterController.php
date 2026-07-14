<?php

namespace App\Modules\Parameter\Controllers;

use App\Core\Http\Controllers\BaseController;
use App\Modules\Parameter\Requests\StoreParameterRequest;
use App\Modules\Parameter\Requests\UpdateParameterRequest;
use App\Modules\Parameter\Resources\ParameterResource;
use App\Modules\Parameter\Services\ParameterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParameterController extends BaseController
{
    public function __construct(protected ParameterService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category']);
        if ($request->has('is_active')) {
            $filters['is_active'] = $request->boolean('is_active');
        }
        $data = $this->service->list($filters, $request->integer('per_page', 100));
        return $this->paginated($data, ParameterResource::class);
    }

    public function store(StoreParameterRequest $request): JsonResponse
    {
        $parameter = $this->service->create($request->validated());
        return $this->created(new ParameterResource($parameter), 'Paramètre créé avec succès');
    }

    public function show(string $id): JsonResponse
    {
        return $this->success(new ParameterResource($this->service->find($id)));
    }

    public function update(UpdateParameterRequest $request, string $id): JsonResponse
    {
        $parameter = $this->service->update($id, $request->validated());
        return $this->success(new ParameterResource($parameter), 'Paramètre mis à jour avec succès');
    }

    public function destroy(string $id): JsonResponse
    {
        $parameter = $this->service->find($id);
        $usage = $this->service->usageLabel($parameter);
        if ($usage) {
            return $this->error("Ce paramètre est utilisé et ne peut pas être supprimé ({$usage}). Désactivez-le à la place.", 422);
        }
        $this->service->delete($id);
        return $this->success(null, 'Paramètre supprimé avec succès');
    }
}
