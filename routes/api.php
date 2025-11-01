<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RolePermissionController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/users', [UserController::class, 'index'])->middleware('permission:user.view');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:user.create');
    Route::get('/users/{id}', [UserController::class, 'show'])->middleware('permission:user.view');
    Route::put('/users/{id}', [UserController::class, 'update'])->middleware('permission:user.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('permission:user.delete');

    Route::put('/user/profile', [UserController::class, 'updateSelf']);
});

Route::middleware(['auth:sanctum', 'role:super-admin'])->group(function () {
    // ROLE
    Route::get('/roles', [RolePermissionController::class, 'getRoles']);
    Route::post('/roles', [RolePermissionController::class, 'createRole']);
    // PERMISSION
    Route::get('/permissions', [RolePermissionController::class, 'getPermissions']);
    Route::post('/permissions', [RolePermissionController::class, 'addPermission']);
    Route::get('/roles/{role}/permissions', [RolePermissionController::class, 'getAllRolesWithPermissions']);
    Route::post('/roles/{role}/assign-permission', [RolePermissionController::class, 'assignPermissionToRole']);
    Route::delete('/roles/{role}/remove-permission', [RolePermissionController::class, 'removePermissionFromRole']);
    // USER ROLE MANAGEMENT
    Route::post('/users/{id}/assign-role', [RolePermissionController::class, 'assignRoleToUser']);
    Route::delete('/users/{id}/remove-role', [RolePermissionController::class, 'removeRoleFromUser']);
});
