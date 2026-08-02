<?php

namespace App\Core\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Yajra\DataTables\Facades\DataTables;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(array $filters = [], array $relations = []): Collection
    {
        return $this->applyFilters($this->model->with($relations), $filters)->get();
    }

    public function paginate(int $perPage = 15, array $filters = [], array $relations = [], string $sortBy = 'created_at', string $sortDir = 'desc', array $withCount = [], array $withSum = []): LengthAwarePaginator
    {
        $query = $this->model->with($relations);
        if (!empty($withCount)) {
            $query = $query->withCount($withCount);
        }
        foreach ($withSum as $relation => $column) {
            $query = $query->withSum($relation, $column);
        }
        $query = $this->applyFilters($query, $filters);
        $query = $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    public function findById(string $id, array $relations = []): ?Model
    {
        return $this->model->with($relations)->find($id);
    }

    public function findByIdOrFail(string $id, array $relations = [], array $withSum = []): Model
    {
        $query = $this->model->with($relations);
        foreach ($withSum as $relation => $column) {
            $query = $query->withSum($relation, $column);
        }
        return $query->findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(string $id, array $data): Model
    {
        $record = $this->findByIdOrFail($id);
        $record->update($data);
        return $record->fresh();
    }

    public function delete(string $id): bool
    {
        $record = $this->findByIdOrFail($id);
        return $record->delete();
    }

    public function restore(string $id): Model
    {
        $record = $this->model->withTrashed()->findOrFail($id);
        $record->restore();
        return $record->fresh();
    }

    public function findByField(string $field, $value, array $relations = []): Collection
    {
        return $this->model->with($relations)->where($field, $value)->get();
    }

    public function search(string $term, array $searchFields, array $relations = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with($relations);

        $query->where(function (Builder $q) use ($term, $searchFields) {
            foreach ($searchFields as $field) {
                $q->orWhere($field, 'LIKE', "%{$term}%");
            }
        });

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Return a Yajra DataTables response for server-side processing.
     */
    public function datatable(array $filters = [], array $relations = [], ?callable $customizer = null, array $withCount = [])
    {
        $query = $this->model->with($relations);
        if (!empty($withCount)) {
            $query = $query->withCount($withCount);
        }
        $query = $this->applyFilters($query, $filters);

        $dataTable = DataTables::eloquent($query);

        if ($customizer) {
            $customizer($dataTable);
        }

        return $dataTable;
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $field => $value) {
            // 1. Ignorer les valeurs nulles, vides ou indéfinies
            if ($value === null || $value === '') {
                continue;
            }

            // 2. Normalisation des valeurs booléennes issues de query string ("true"/"false")
            $isTrueString = is_string($value) && strtolower($value) === 'true';
            $isFalseString = is_string($value) && strtolower($value) === 'false';
            $isBool = is_bool($value) || $isTrueString || $isFalseString;
            $boolVal = is_bool($value) ? $value : $isTrueString;

            // 3. Gestion spécifique du champ 'search' (Recherche générique LIKE)
            if ($field === 'search') {
                $query->where(function (Builder $q) use ($value) {
                    // Adaptez ou surchargez les colonnes selon le modèle si besoin
                    $columns = property_exists($this, 'searchableColumns') ? $this->searchableColumns : ['code', 'note'];
                    foreach ($columns as $index => $column) {
                        if ($index === 0) {
                            $q->where($column, 'LIKE', "%{$value}%");
                        } else {
                            $q->orWhere($column, 'LIKE', "%{$value}%");
                        }
                    }
                });
                continue;
            }

            // 4. Gestion des filtres de dates de création
            if ($field === 'date_from') {
                $query->whereDate('created_at', '>=', $value);
                continue;
            }
            if ($field === 'date_to') {
                $query->whereDate('created_at', '<=', $value);
                continue;
            }

            // 5. Gestion des vérifications de présence/nullité pour les IDs d'anciennes bases (ex: legacy_id)
            if (str_ends_with($field, '_id') && $isBool) {
                $boolVal ? $query->whereNotNull($field) : $query->whereNull($field);
                continue;
            }

            // 6. Application des filtres booléens classiques
            if ($isBool) {
                $query->where($field, $boolVal);
                continue;
            }

            // 7. Filtre standard par égalité (IDs, statuts, enum, etc.)
            $query->where($field, $value);
        }

        return $query;
    }
}
