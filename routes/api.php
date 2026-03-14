<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    Route::get('/', 'InstitutionController@index');
    Route::get('/{institutionId}', 'InstitutionController@show');
});

/*
|--------------------------------------------------------------------------
| Multitenant Routes (Requieren Header X-Institution-ID)
|--------------------------------------------------------------------------
*/
Route::middleware(['tenant'])->group(function () {

    // ❌ APAGADO PORQUE SACAMOS SANCTUM
    // Si más adelante necesitás login, usaremos 'auth:api' estándar de L5.5
    // Route::middleware('auth:api')->get('/user', function (Request $request) {
    //     return $request->user();
    // });

    // --- Alumnos ---
    Route::prefix('alumnos')->group(function () {
        Route::get('/', 'AlumnoController@index');
        Route::get('/{studentId}', 'AlumnoController@show');
    });

    // --- Tareas / Tasks ---
    Route::prefix('tasks')->group(function () {
        Route::get('/', 'TaskController@index');
        Route::get('/stats', 'TaskController@getTaskStats');
        Route::get('/{taskId}', 'TaskController@show');
        Route::get('/student/{studentId}', 'TaskController@studentTasks');
        Route::post('/resolution', 'TaskController@submitResolution');
    });

    // --- Calificaciones / Grades ---
    Route::prefix('grades')->group(function () {
        Route::get('/student/{studentId}', 'GradeController@studentGrades');
        Route::get('/student/{studentId}/summary', 'GradeController@gradesSummary');
    });

    // --- Noticias / News ---
    Route::prefix('news')->group(function () {
        Route::get('/student/{studentId}', 'NewsController@studentNews');
        Route::get('/student/{studentId}/summary', 'NewsController@newsSummary');
        Route::post('/student/{studentId}/{newsId}/read', 'NewsController@markAsRead');
    });

    // --- Asistencia / Attendance ---
    Route::prefix('attendance')->group(function () {
        Route::get('/student/{studentId}/summary', 'AttendanceController@summary');
        Route::get('/student/{studentId}/subjects', 'AttendanceController@subjectsAttendance');
        Route::get('/student/{studentId}/subject/{subjectId}', 'AttendanceController@subjectDetail');
    });
    
    // --- Inscripciones / Enrollments ---
    Route::prefix('inscripciones')->group(function () {
        Route::get('/disponibles', 'InscripcionController@disponibles');
        Route::post('/inscribir', 'InscripcionController@inscribir');
        Route::post('/baja', 'InscripcionController@darDeBaja');
    });
});