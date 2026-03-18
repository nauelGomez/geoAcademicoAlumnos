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

    //  APAGADO PORQUE SACAMOS SANCTUM
    // 
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

    // --- Dashboard ---
    Route::prefix('dashboard')->group(function () {
        Route::get('/', 'DashboardController@getStudentDashboard');
    });

    // --- Calificaciones / Course Grades ---
    Route::prefix('grades')->group(function () {
        Route::get('/student/{studentId}', 'CourseGradeController@studentGrades');
    });

    // --- Documentación / Documentation ---
    Route::prefix('documentation')->group(function () {
        Route::get('/student/{studentId}', 'DocumentationController@studentDocumentation');
    });

    // Módulo de Test Virtuales
    Route::prefix('virtual-tests')->group(function () {
        // Listado principal (Panel General de Test)
        Route::get('/student/{studentId}', 'VirtualTestController@index');

        // Ver detalle de un test específico (ver_test.php)
        Route::get('/{testId}/student/{studentId}', 'VirtualTestController@show');
    });

    Route::prefix('correlativities')->group(function () {
        Route::get('/student/{studentId}', 'CorrelativityController@index');
    });

    Route::prefix('exam-sessions')->group(function () {
        Route::get('/student/{studentId}', 'ExamSessionController@index');
    });

    Route::prefix('certificates')->group(function () {
        Route::get('/student/{studentId}', 'CertificateController@index');
        Route::get('/student/{studentId}/create', 'CertificateController@create');
        Route::post('/student/{studentId}', 'CertificateController@store');
    });

    Route::prefix('posts')->group(function () {
        Route::get('/student/{studentId}', 'PostController@index');
    });

    Route::prefix('profile')->group(function () {
        Route::get('/student/{studentId}', 'ProfileController@show');
        Route::put('/student/{studentId}', 'ProfileController@update');
    });

    Route::prefix('messages')->group(function () {
        Route::get('/student/{studentId}/recipients', 'MessageController@create');
        Route::get('/student/{studentId}/chat/{codigo}', 'MessageController@show');

        Route::post('/student/{studentId}', 'MessageController@store');
        Route::post('/student/{studentId}/chat/{codigo}', 'MessageController@reply');
    });

    Route::prefix('app-familias')->namespace('AppFamilias')->group(function () {
        // Fijate que acá solo llamamos al controlador por su nombre corto
        Route::get('/linked-students', 'FamilyStudentController@index');
        
        // DASHBOARD DEL ALUMNO SELECCIONADO
        Route::get('/dashboard/student/{studentId}', 'FamilyDashboardController@show');
        
        // AGENDA COMPLETA DEL ALUMNO
        Route::get('/agenda/student/{studentId}', 'AgendaController@index');
        
        // TAREAS DE INTENSIFICACIÓN
        Route::get('/intensification/student/{studentId}', 'IntensificationController@index');
        
        // MUROS DE NOVEDADES
        Route::get('/walls/student/{studentId}', 'FamilyWallController@index');
        
        // LIBRO DE CALIFICACIONES
        Route::get('/grades/student/{studentId}', 'GradeController@index');
        
        // DETALLE DE INASISTENCIAS
        Route::get('/attendance/student/{studentId}', 'AttendanceController@index');
        
        // INFORMES PEDAGÓGICOS
        Route::get('/reports/student/{studentId}', 'ReportController@index');
        
        // COMUNICACIONES
        Route::get('/announcements/student/{studentId}', 'AnnouncementController@index');
        Route::get('/announcements/{tipo}/{code}/student/{studentId}', 'AnnouncementController@show');
        
        // AUTORIZACIONES DE RETIRO
        Route::get('/authorizations/student/{studentId}', 'AuthorizationController@index');
        Route::post('/authorizations/person/student/{studentId}', 'AuthorizationController@storePerson');
        Route::delete('/authorizations/person/{id}', 'AuthorizationController@destroyPerson');
        Route::post('/authorizations/notice/student/{studentId}', 'AuthorizationController@storeNotice');
        Route::delete('/authorizations/notice/{id}', 'AuthorizationController@destroyNotice');
        
        // MESAS DE EXAMEN
        Route::get('/exam-boards/student/{studentId}', 'ExamBoardController@index');
        
        // PUBLICACIONES/DIFUSIONES
        Route::get('/posts/student/{studentId}', 'PostController@index');
        Route::get('/posts/{postId}/student/{studentId}', 'PostController@show');
        
        // PERFIL DEL ALUMNO
        Route::get('/profile/student/{studentId}', 'ProfileController@show');
        Route::post('/profile/student/{studentId}/update', 'ProfileController@update');
        Route::post('/profile/student/{studentId}/photo', 'ProfileController@updatePhoto');
        
        // MENSAJERÍA BIDIRECCIONAL
        Route::get('/messaging/student/{studentId}', 'MessagingController@index');
        Route::get('/messaging/chat/{code}', 'MessagingController@show');
        Route::get('/messaging/recipients/student/{studentId}', 'MessagingController@recipients');
        Route::post('/messaging/send', 'MessagingController@store');
        
        // AUTO-INSCRIPCIONES A MATERIAS GRUPALES
        Route::get('/enrollments/student/{studentId}', 'EnrollmentController@index');
        Route::post('/enrollments/student/{studentId}', 'EnrollmentController@store');
    });
});
