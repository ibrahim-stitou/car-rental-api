<?php

namespace App\Core\Http\Controllers;

use App\Core\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

/**
 * @OA\Info(
 *   title="Car Rental API - GES-CARS-2026",
 *   version="1.0.0",
 *   description="API RESTful pour la gestion de location de voitures",
 *   @OA\Contact(email="admin@ges-cars.ma")
 * )
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT",
 *   description="Entrer le token JWT"
 * )
 * @OA\Server(url="/api/v1", description="API V1")
 */
abstract class BaseController extends Controller
{
    use ApiResponse, AuthorizesRequests, ValidatesRequests;
}

