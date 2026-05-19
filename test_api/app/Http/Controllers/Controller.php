<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'API Livres',
    description: 'API REST de gestion des livres avec authentification Sanctum.',
)]
#[OA\Server(
    url: 'http://127.0.0.1:8000',
    description: 'Serveur local',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'token',
    description: 'Token Sanctum obtenu via POST /api/v1/login',
)]
abstract class Controller
{
    //
}
