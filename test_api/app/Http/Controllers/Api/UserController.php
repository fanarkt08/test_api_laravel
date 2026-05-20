<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Post(
        path: '/register',
        summary: "Inscription d'un utilisateur",
        parameters: [
            new OA\Parameter(name: 'Accept', in: 'header', required: true, schema: new OA\Schema(type: 'string', default: 'application/json')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Jane Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'jane@example.com'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'secret123'),
                ],
            ),
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur créé',
                content: new OA\JsonContent(
                    example: [
                        'token' => '1|P24uxIp4amrYV0fRN4Caojycu7AsZbwEH5fVPG6r19c8f138',
                        'user'  => ['id' => 1, 'name' => 'Jane Doe', 'email' => 'jane@example.com', 'created_at' => '2026-05-19T10:00:00Z'],
                    ],
                ),
            ),
            new OA\Response(
                response: 422,
                description: 'Données invalides',
                content: new OA\JsonContent(
                    example: ['message' => 'The email has already been taken.', 'errors' => ['email' => ['The email has already been taken.']]],
                ),
            ),
        ],
    )]
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users',
            'password' => 'required|min:8',
        ]);

        $user  = User::create($validated);
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user], 201);
    }

    #[OA\Post(
        path: '/login',
        summary: 'Connexion et génération du token',
        parameters: [
            new OA\Parameter(name: 'Accept', in: 'header', required: true, schema: new OA\Schema(type: 'string', default: 'application/json')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'secret123'),
                ],
            ),
        ),
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connexion réussie',
                content: new OA\JsonContent(
                    example: [
                        'token' => '1|P24uxIp4amrYV0fRN4Caojycu7AsZbwEH5fVPG6r19c8f138',
                        'user'  => ['id' => 1, 'name' => 'Jane Doe', 'email' => 'jane@example.com', 'created_at' => '2026-05-19T10:00:00Z'],
                    ],
                ),
            ),
            new OA\Response(
                response: 401,
                description: 'Identifiants invalides',
                content: new OA\JsonContent(
                    example: ['message' => 'Identifiants invalides.'],
                ),
            ),
            new OA\Response(
                response: 422,
                description: 'Données invalides',
                content: new OA\JsonContent(
                    example: ['message' => 'The email field must be a valid email address.', 'errors' => ['email' => ['The email field must be a valid email address.']]],
                ),
            ),
            new OA\Response(response: 429, description: 'Trop de tentatives (max 10/min)'),
        ],
    )]
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($validated)) {
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    #[OA\Post(
        path: '/logout',
        summary: 'Déconnexion — supprime le token courant',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'Accept', in: 'header', required: true, schema: new OA\Schema(type: 'string', default: 'application/json')),
            new OA\Parameter(name: 'Authorization', in: 'header', required: true, schema: new OA\Schema(type: 'string', default: 'Bearer <token>')),
        ],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 204, description: 'Déconnexion réussie — aucun contenu'),
            new OA\Response(
                response: 401,
                description: 'Non authentifié',
                content: new OA\JsonContent(
                    example: ['message' => 'Unauthenticated.'],
                ),
            ),
        ],
    )]
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
