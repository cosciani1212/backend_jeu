<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\StructureController;
use App\Http\Controllers\Api\CategorieController;
use App\Http\Controllers\Api\EquipeController;
use App\Http\Controllers\Api\EvenementController;
use App\Http\Controllers\Api\JuryController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// AUTHENTIFICATION
Route::post('login', [AuthController::class, 'login']);

Route::post('/register-structure', [AuthController::class, 'registerStructure']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('me', [AuthController::class, 'me']);

    // ROLES
    Route::get('roles/search', [RoleController::class, 'search']);
    Route::apiResource('roles', RoleController::class);

    // USERS
    Route::get('users/search', [UserController::class, 'search']);
    Route::apiResource('users', UserController::class);

    // STRUCTURES
    Route::get('structures/search', [StructureController::class, 'search']);
    Route::apiResource('structures', StructureController::class);

    // CATEGORIES
    Route::get('categories/search', [CategorieController::class, 'search']);
    Route::apiResource('categories', CategorieController::class);

    // EQUIPES
    Route::get('equipes/search', [EquipeController::class, 'search']);
    Route::apiResource('equipes', EquipeController::class);

    // EVENEMENTS
    Route::get('evenements/search', [EvenementController::class, 'search']);
    Route::apiResource('evenements', EvenementController::class);

    // JURYS
    Route::get('jurys/search', [JuryController::class, 'search']);
    Route::apiResource('jurys', JuryController::class);
});
