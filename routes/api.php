<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');




Route::get('/check_api', function () {
    return "check api";
});





Route::prefix('users')->group(function () {
    //Route::get('/', [UserController::class, 'index']);
    Route::post('/registration', [UserController::class, 'registration']);
    Route::post('/login', [UserController::class, 'login']);
    Route::get('/popular-services', [ApiController::class, 'popularServices']);
    Route::post('/search-services', [ApiController::class, 'searchServices']);
    Route::get('/get-categories', [ApiController::class, 'getCategories']);
    Route::middleware('auth:sanctum','authMiddleware')->group(function () {
        // add services 
        Route::post('/add_service', [UserController::class, 'addUserService']);
        Route::post('/add_location', [UserController::class, 'addUserLocation']);
        Route::post('/get_user_services', [UserController::class, 'getUserServices']);
        Route::post('/get_user_locations', [UserController::class, 'getUserLocations']);
        Route::post('/switch_user', [UserController::class, 'switchUser']);
        Route::post('/edit-profile', [UserController::class, 'editProfile']);
        Route::post('/update-profile', [UserController::class, 'updateProfile']);
        Route::post('/logout', [UserController::class, 'logout']);
        Route::post('/update-profile-image', [UserController::class, 'updateProfileImage']);
        Route::post('/change-password', [UserController::class, 'changePassword']);

        Route::post('/questions-answer', [ApiController::class, 'questionAnswer']);
        
    });
    // Route::get('/{id}', [UserController::class, 'show']);
    // Route::put('/{id}', [UserController::class, 'update']);
    //Route::delete('/{id}', [UserController::class, 'destroy']);

    // add services 

    // Route::post('/add_service', [UserController::class, 'addUserService']);
    // Route::post('/add_location', [UserController::class, 'addUserLocation']);
    // Route::post('/get_user_services', [UserController::class, 'getUserServices']);
    // Route::post('/get_user_locations', [UserController::class, 'getUserLocations']);
    // Route::post('/switch_user', [UserController::class, 'switchUser']);
    // Route::get('/get-categories', [UserController::class, 'getCategories']);
});