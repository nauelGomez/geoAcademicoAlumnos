<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InscripcionController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/hello', function () {
    return response()->json([
        'message' => 'Hello World',
        'status' => 'success',
        'project' => 'geoAcademicoAlumnos'
    ]);
});
Route::prefix('inscripciones')->group(function () {
        // El GET que ya tenías funcionando (ojo que en tu último código le pusiste getDisponibles)
        Route::get('/disponibles', [InscripcionController::class, 'getDisponibles']);
        
        // Los dos POST nuevos para gestionar los cupos
        Route::post('/inscribir', [InscripcionController::class, 'inscribir']);
        Route::post('/dar-de-baja', [InscripcionController::class, 'darDeBaja']);
    });