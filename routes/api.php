<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\InscripcionController;
/*
|--------------------------------------------------------------------------
| Public & System Routes
|--------------------------------------------------------------------------
*/
Route::get('/hello', function () {
    return response()->json([
        'message' => 'Hello World',
        'status'  => 'success',
        'project' => 'geoAcademicoAlumnos'
    ]);
});
// Estas rutas consultan la DB Master para listar los colegios
Route::prefix('institutions')->group(function () {
    Route::get('/', [InstitutionController::class, 'index']);
    Route::get('/{institutionId}', [InstitutionController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Multitenant Routes (Requieren Header X-Institution-ID)
|--------------------------------------------------------------------------
*/
Route::middleware(['tenant'])->group(function () {

    // --- Usuario Autenticado ---
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    // --- Alumnos ---
    Route::prefix('alumnos')->group(function () {
        Route::get('/', [AlumnoController::class, 'index']);
        Route::get('/{studentId}', [AlumnoController::class, 'show']);
    });

    // --- Tareas / Tasks ---
    Route::prefix('tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::get('/stats', [TaskController::class, 'getTaskStats']);
        Route::get('/{taskId}', [TaskController::class, 'show']);
        Route::get('/student/{studentId}', [TaskController::class, 'studentTasks']);
        Route::post('/resolution', [TaskController::class, 'submitResolution']);
    });

    // --- Calificaciones / Grades ---
    Route::prefix('grades')->group(function () {
        Route::get('/student/{studentId}', [GradeController::class, 'studentGrades']);
        Route::get('/student/{studentId}/summary', [GradeController::class, 'gradesSummary']);
    });

    // --- Noticias / News ---
    Route::prefix('news')->group(function () {
        Route::get('/student/{studentId}', [NewsController::class, 'studentNews']);
        Route::get('/student/{studentId}/summary', [NewsController::class, 'newsSummary']);
        Route::post('/student/{studentId}/{newsId}/read', [NewsController::class, 'markAsRead']);
    });

    // --- Asistencia / Attendance ---
    Route::prefix('attendance')->group(function () {
        Route::get('/student/{studentId}/summary', [AttendanceController::class, 'summary']);
        Route::get('/student/{studentId}/subjects', [AttendanceController::class, 'subjectsAttendance']);
        Route::get('/student/{studentId}/subject/{subjectId}', [AttendanceController::class, 'subjectDetail']);
    });
    
    // --- Inscripciones / Enrollments ---
    Route::prefix('inscripciones')->group(function () {
        Route::get('/disponibles', [InscripcionController::class, 'disponibles']);
        Route::post('/inscribir', [InscripcionController::class, 'inscribir']);
    });
});