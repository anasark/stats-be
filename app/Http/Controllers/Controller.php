<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Apify Statistics API",
 *     version="1.0.0",
 *     description="API for social media statistics dashboard"
 * )
 * @OA\Server(url="http://localhost:8001", description="Local Docker")
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your Sanctum token"
 * )
 */
abstract class Controller
{
    //
}
