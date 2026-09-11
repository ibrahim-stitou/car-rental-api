<?php

namespace App\Modules\Vehicle\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;

class VehicleRepository extends BaseRepository
{
    public function __construct(Vehicle $model)
    {
        parent::__construct($model);
    }

    public function getSearchFields(): array
    {
        return [
            'brand',
            'model',
            'registration_number',
            'vin',
            'color',
        ];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        /*
         * ============================================================
         * RETURNING TODAY
         * ============================================================
         */
        if (array_key_exists('returning_today', $filters)) {
            $wantsReturningToday = filter_var(
                $filters['returning_today'],
                FILTER_VALIDATE_BOOLEAN
            );

            unset($filters['returning_today']);

            if ($wantsReturningToday) {
                $query->whereHas('reservations', function (Builder $q) {
                    $q->where('status', 'active')
                        ->whereDate('return_date', today());
                });
            }
        }

        /*
         * ============================================================
         * DOCUMENTS STATUS
         * ============================================================
         */
        if (!empty($filters['documents_status'])) {
            $status = $filters['documents_status'];

            /*
             * IMPORTANT:
             * documents_status is not a column in vehicles.
             * Remove it before calling BaseRepository.
             */
            unset($filters['documents_status']);

            $today = today();
            $expiringSoonDate = today()->addDays(30);

            match ($status) {

                /*
                 * ====================================================
                 * EXPIRED
                 * ====================================================
                 *
                 * At least one latest document is expired.
                 */
                'expired' => $query->where(function (Builder $q) use ($today) {

                    /*
                     * Latest passed technical inspection
                     */
                    $q->whereHas('technicalInspections', function (Builder $q) use ($today) {
                        $q->where('result', 'passed')
                            ->whereDate('expiry_date', '<=', $today)
                            ->whereNotExists(function ($subQuery) {
                                $subQuery->selectRaw('1')
                                    ->from('technical_inspections as ti2')
                                    ->whereColumn(
                                        'ti2.vehicle_id',
                                        'technical_inspections.vehicle_id'
                                    )
                                    ->where('ti2.result', 'passed')
                                    ->whereColumn(
                                        'ti2.expiry_date',
                                        '>',
                                        'technical_inspections.expiry_date'
                                    );
                            });
                    })

                        /*
                         * Latest insurance
                         */
                        ->orWhereHas('insurances', function (Builder $q) use ($today) {
                            $q->whereDate('end_date', '<=', $today)
                                ->whereNotExists(function ($subQuery) {
                                    $subQuery->selectRaw('1')
                                        ->from('insurances as i2')
                                        ->whereColumn(
                                            'i2.vehicle_id',
                                            'insurances.vehicle_id'
                                        )
                                        ->whereColumn(
                                            'i2.end_date',
                                            '>',
                                            'insurances.end_date'
                                        );
                                });
                        })

                        /*
                         * Latest vignette
                         */
                        ->orWhereHas('vignettes', function (Builder $q) use ($today) {
                            $q->whereDate('expiry_date', '<=', $today)
                                ->whereNotExists(function ($subQuery) {
                                    $subQuery->selectRaw('1')
                                        ->from('vignettes as v2')
                                        ->whereColumn(
                                            'v2.vehicle_id',
                                            'vignettes.vehicle_id'
                                        )
                                        ->whereColumn(
                                            'v2.expiry_date',
                                            '>',
                                            'vignettes.expiry_date'
                                        );
                                });
                        });
                }),

                /*
                 * ====================================================
                 * EXPIRING SOON
                 * ====================================================
                 *
                 * No latest document is expired.
                 *
                 * At least one latest document expires
                 * within the next 30 days.
                 */
                'expiring_soon' => $query

                    /*
                     * No expired technical inspection
                     */
                    ->whereDoesntHave('technicalInspections', function (Builder $q) use ($today) {
                        $q->where('result', 'passed')
                            ->whereDate('expiry_date', '<=', $today)
                            ->whereNotExists(function ($subQuery) {
                                $subQuery->selectRaw('1')
                                    ->from('technical_inspections as ti2')
                                    ->whereColumn(
                                        'ti2.vehicle_id',
                                        'technical_inspections.vehicle_id'
                                    )
                                    ->where('ti2.result', 'passed')
                                    ->whereColumn(
                                        'ti2.expiry_date',
                                        '>',
                                        'technical_inspections.expiry_date'
                                    );
                            });
                    })

                    /*
                     * No expired insurance
                     */
                    ->whereDoesntHave('insurances', function (Builder $q) use ($today) {
                        $q->whereDate('end_date', '<=', $today)
                            ->whereNotExists(function ($subQuery) {
                                $subQuery->selectRaw('1')
                                    ->from('insurances as i2')
                                    ->whereColumn(
                                        'i2.vehicle_id',
                                        'insurances.vehicle_id'
                                    )
                                    ->whereColumn(
                                        'i2.end_date',
                                        '>',
                                        'insurances.end_date'
                                    );
                            });
                    })

                    /*
                     * No expired vignette
                     */
                    ->whereDoesntHave('vignettes', function (Builder $q) use ($today) {
                        $q->whereDate('expiry_date', '<=', $today)
                            ->whereNotExists(function ($subQuery) {
                                $subQuery->selectRaw('1')
                                    ->from('vignettes as v2')
                                    ->whereColumn(
                                        'v2.vehicle_id',
                                        'vignettes.vehicle_id'
                                    )
                                    ->whereColumn(
                                        'v2.expiry_date',
                                        '>',
                                        'vignettes.expiry_date'
                                    );
                            });
                    })

                    /*
                     * At least one latest document is expiring soon
                     */
                    ->where(function (Builder $q) use ($today, $expiringSoonDate) {

                        /*
                         * Technical inspection
                         */
                        $q->whereHas('technicalInspections', function (Builder $q) use ($today, $expiringSoonDate) {
                            $q->where('result', 'passed')
                                ->whereDate('expiry_date', '>', $today)
                                ->whereDate('expiry_date', '<=', $expiringSoonDate)
                                ->whereNotExists(function ($subQuery) {
                                    $subQuery->selectRaw('1')
                                        ->from('technical_inspections as ti2')
                                        ->whereColumn(
                                            'ti2.vehicle_id',
                                            'technical_inspections.vehicle_id'
                                        )
                                        ->where('ti2.result', 'passed')
                                        ->whereColumn(
                                            'ti2.expiry_date',
                                            '>',
                                            'technical_inspections.expiry_date'
                                        );
                                });
                        })

                            /*
                             * Insurance
                             */
                            ->orWhereHas('insurances', function (Builder $q) use ($today, $expiringSoonDate) {
                                $q->whereDate('end_date', '>', $today)
                                    ->whereDate('end_date', '<=', $expiringSoonDate)
                                    ->whereNotExists(function ($subQuery) {
                                        $subQuery->selectRaw('1')
                                            ->from('insurances as i2')
                                            ->whereColumn(
                                                'i2.vehicle_id',
                                                'insurances.vehicle_id'
                                            )
                                            ->whereColumn(
                                                'i2.end_date',
                                                '>',
                                                'insurances.end_date'
                                            );
                                    });
                            })

                            /*
                             * Vignette
                             */
                            ->orWhereHas('vignettes', function (Builder $q) use ($today, $expiringSoonDate) {
                                $q->whereDate('expiry_date', '>', $today)
                                    ->whereDate('expiry_date', '<=', $expiringSoonDate)
                                    ->whereNotExists(function ($subQuery) {
                                        $subQuery->selectRaw('1')
                                            ->from('vignettes as v2')
                                            ->whereColumn(
                                                'v2.vehicle_id',
                                                'vignettes.vehicle_id'
                                            )
                                            ->whereColumn(
                                                'v2.expiry_date',
                                                '>',
                                                'vignettes.expiry_date'
                                            );
                                    });
                            });
                    }),

                /*
                 * ====================================================
                 * ACTIVE
                 * ====================================================
                 *
                 * All three latest documents must exist
                 * and expire after 30 days.
                 */
                'active' => $query

                    /*
                     * Latest technical inspection
                     */
                    ->whereHas('technicalInspections', function (Builder $q) use ($expiringSoonDate) {
                        $q->where('result', 'passed')
                            ->whereDate('expiry_date', '>', $expiringSoonDate)
                            ->whereNotExists(function ($subQuery) {
                                $subQuery->selectRaw('1')
                                    ->from('technical_inspections as ti2')
                                    ->whereColumn(
                                        'ti2.vehicle_id',
                                        'technical_inspections.vehicle_id'
                                    )
                                    ->where('ti2.result', 'passed')
                                    ->whereColumn(
                                        'ti2.expiry_date',
                                        '>',
                                        'technical_inspections.expiry_date'
                                    );
                            });
                    })

                    /*
                     * Latest insurance
                     */
                    ->whereHas('insurances', function (Builder $q) use ($expiringSoonDate) {
                        $q->whereDate('end_date', '>', $expiringSoonDate)
                            ->whereNotExists(function ($subQuery) {
                                $subQuery->selectRaw('1')
                                    ->from('insurances as i2')
                                    ->whereColumn(
                                        'i2.vehicle_id',
                                        'insurances.vehicle_id'
                                    )
                                    ->whereColumn(
                                        'i2.end_date',
                                        '>',
                                        'insurances.end_date'
                                    );
                            });
                    })

                    /*
                     * Latest vignette
                     */
                    ->whereHas('vignettes', function (Builder $q) use ($expiringSoonDate) {
                        $q->whereDate('expiry_date', '>', $expiringSoonDate)
                            ->whereNotExists(function ($subQuery) {
                                $subQuery->selectRaw('1')
                                    ->from('vignettes as v2')
                                    ->whereColumn(
                                        'v2.vehicle_id',
                                        'vignettes.vehicle_id'
                                    )
                                    ->whereColumn(
                                        'v2.expiry_date',
                                        '>',
                                        'vignettes.expiry_date'
                                    );
                            });
                    }),

                /*
                 * Unknown status
                 */
                default => $query,
            };
        }

        /*
         * Apply normal Vehicle filters.
         *
         * documents_status has already been removed,
         * so BaseRepository won't execute:
         *
         * WHERE documents_status = ...
         */
        return parent::applyFilters($query, $filters);
    }
}
