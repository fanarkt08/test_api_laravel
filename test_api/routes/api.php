<?php

use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});

// Auth
Route::post('/register', [UserController::class, 'register']);
Route::middleware('throttle:10,1')->post('/login', [UserController::class, 'login']);

// Livres - routes publiques
Route::apiResource('books', BookController::class)->only(['index', 'show']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
    Route::apiResource('books', BookController::class)->only(['store', 'update', 'destroy']);
});
