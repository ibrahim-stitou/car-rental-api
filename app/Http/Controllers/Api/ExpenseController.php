<?php

namespace App\Http\Controllers\Api;

use App\Core\Http\Controllers\BaseController;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Expense::with(['agency:id,name', 'vehicle:id,brand,model,registration_number'])
            ->when($request->agency_id, fn($q) => $q->where('agency_id', $request->agency_id))
            ->when($request->vehicle_id, fn($q) => $q->where('vehicle_id', $request->vehicle_id))
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->orderByDesc('expense_date');

        $perPage = (int) ($request->per_page ?? 15);
        $paginated = $query->paginate($perPage);

        return $this->paginated($paginated, null);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title'          => 'required|string|max:255',
            'category'       => 'required|in:fuel,maintenance,insurance,vignette,inspection,repair,cleaning,administrative,salary,rent,utilities,other',
            'amount'         => 'required|numeric|min:0',
            'expense_date'   => 'required|date',
            'agency_id'      => 'nullable|uuid|exists:agencies,id',
            'vehicle_id'     => 'nullable|uuid|exists:vehicles,id',
            'payment_method' => 'nullable|in:cash,card,bank_transfer,check,online',
            'reference'      => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $expense = Expense::create(array_merge(
            $request->only(['title', 'category', 'amount', 'expense_date', 'agency_id', 'vehicle_id', 'payment_method', 'reference', 'notes']),
            ['recorded_by' => Auth::id()]
        ));

        return $this->created($expense->load(['agency:id,name', 'vehicle:id,brand,model,registration_number']), 'Dépense enregistrée');
    }

    public function show(string $id): JsonResponse
    {
        $expense = Expense::with(['agency:id,name', 'vehicle:id,brand,model,registration_number'])->findOrFail($id);
        return $this->success($expense);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $expense = Expense::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title'          => 'sometimes|required|string|max:255',
            'category'       => 'sometimes|required|in:fuel,maintenance,insurance,vignette,inspection,repair,cleaning,administrative,salary,rent,utilities,other',
            'amount'         => 'sometimes|required|numeric|min:0',
            'expense_date'   => 'sometimes|required|date',
            'agency_id'      => 'nullable|uuid|exists:agencies,id',
            'vehicle_id'     => 'nullable|uuid|exists:vehicles,id',
            'payment_method' => 'nullable|in:cash,card,bank_transfer,check,online',
            'reference'      => 'nullable|string|max:255',
            'notes'          => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $expense->update($request->only(['title', 'category', 'amount', 'expense_date', 'agency_id', 'vehicle_id', 'payment_method', 'reference', 'notes']));

        return $this->success($expense->load(['agency:id,name', 'vehicle:id,brand,model,registration_number']), 'Dépense mise à jour');
    }

    public function destroy(string $id): JsonResponse
    {
        Expense::findOrFail($id)->delete();
        return $this->success(null, 'Dépense supprimée');
    }

    public function uploadDocuments(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'documents'   => 'required|array',
            'documents.*' => 'file|max:10240',
        ]);

        $expense = Expense::findOrFail($id);

        if (!$expense instanceof \Spatie\MediaLibrary\HasMedia) {
            return $this->error('Ce modèle ne supporte pas les médias', 500);
        }

        $expense->uploadMultipleMedia($request->file('documents'), 'documents');
        return $this->success($expense->getMediaByCollection('documents'), 'Documents téléversés');
    }

    public function uploadReceipts(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'receipts'   => 'required|array',
            'receipts.*' => 'file|mimes:jpeg,png,pdf|max:5120',
        ]);

        $expense = Expense::findOrFail($id);
        $expense->uploadMultipleMedia($request->file('receipts'), 'receipts');
        return $this->success($expense->getMediaByCollection('receipts'), 'Justificatifs téléversés');
    }

    public function getMedia(string $id): JsonResponse
    {
        $expense = Expense::findOrFail($id);
        return $this->success($expense->getAllMediaFormatted());
    }

    public function deleteMedia(string $id, int $mediaId): JsonResponse
    {
        $expense = Expense::findOrFail($id);
        $expense->media()->findOrFail($mediaId)->delete();
        return $this->success(null, 'Média supprimé');
    }

    public function statistics(Request $request): JsonResponse
    {
        $query = Expense::query()
            ->when($request->agency_id, fn($q) => $q->where('agency_id', $request->agency_id))
            ->when($request->vehicle_id, fn($q) => $q->where('vehicle_id', $request->vehicle_id));

        $total      = (float) (clone $query)->sum('amount');
        $thisMonth  = (float) (clone $query)->whereMonth('expense_date', now()->month)->whereYear('expense_date', now()->year)->sum('amount');
        $lastMonth  = (float) (clone $query)->whereMonth('expense_date', now()->subMonth()->month)->whereYear('expense_date', now()->subMonth()->year)->sum('amount');
        $byCategory = (clone $query)->selectRaw('category, SUM(amount) as total')->groupBy('category')->pluck('total', 'category');

        return $this->success(compact('total', 'thisMonth', 'lastMonth', 'byCategory'));
    }
}
