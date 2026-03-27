<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Importación de Controladores (Habilita Ctrl+Click en el IDE)
|--------------------------------------------------------------------------
*/

// Controladores Principales
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CourseGradeController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\VirtualTestController;
use App\Http\Controllers\CorrelativityController;
use App\Http\Controllers\ExamSessionController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MessageController;

// Controladores de AppFamilias (Usamos alias para evitar conflictos de nombres)
use App\Http\Controllers\AppFamilias\FamilyStudentController;
use App\Http\Controllers\AppFamilias\FamilyDashboardController;
use App\Http\Controllers\AppFamilias\AgendaController;
use App\Http\Controllers\AppFamilias\IntensificationController;
use App\Http\Controllers\AppFamilias\FamilyWallController;
use App\Http\Controllers\AppFamilias\GradeController as FamilyGradeController;
use App\Http\Controllers\AppFamilias\AttendanceController as FamilyAttendanceController;
use App\Http\Controllers\AppFamilias\ReportController;
use App\Http\Controllers\AppFamilias\FamilyPostController;
use App\Http\Controllers\AppFamilias\AnnouncementController;
use App\Http\Controllers\AppFamilias\AuthorizationController;
use App\Http\Controllers\AppFamilias\ExamBoardController;
use App\Http\Controllers\AppFamilias\EnrollmentController;
use App\Http\Controllers\AppFamilias\FamilyAulaController;
use App\Http\Controllers\AppFamilias\FamilyTaskController;
use App\Http\Controllers\AppFamilias\ProfileController as FamilyProfileController;
use App\Http\Controllers\AppFamilias\MessagingController;

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
    Route::get('/', '\\' . InstitutionController::class . '@index');
    Route::get('/{institutionId}', '\\' . InstitutionController::class . '@show');
});

/*
|--------------------------------------------------------------------------
| Multitenant Routes (Requieren Header X-Institution-ID)
|--------------------------------------------------------------------------
*/
Route::middleware(['tenant'])->group(function () {

    // --- Alumnos ---
    Route::prefix('alumnos')->group(function () {
        Route::get('/', '\\' . AlumnoController::class . '@index');
        Route::get('/{studentId}', '\\' . AlumnoController::class . '@show');
    });

    // --- Tareas / Tasks ---
    Route::prefix('tasks')->group(function () {
        Route::get('/', '\\' . TaskController::class . '@index');
        Route::get('/stats', '\\' . TaskController::class . '@getTaskStats');
        Route::get('/{taskId}', '\\' . TaskController::class . '@show');
        Route::get('/student/{studentId}', '\\' . TaskController::class . '@studentTasks');
        Route::post('/resolution', '\\' . TaskController::class . '@submitResolution');
    });

    // --- Calificaciones / Grades ---
    Route::prefix('grades')->group(function () {
        Route::get('/student/{studentId}', '\\' . GradeController::class . '@studentGrades');
        Route::get('/student/{studentId}/summary', '\\' . GradeController::class . '@gradesSummary');
        
        // --- Course Grades (Estaba duplicado el prefijo 'grades' en tu código, lo agrupamos) ---
        // Asumo que CourseGradeController y GradeController manejan cosas distintas bajo la misma URL
        Route::get('/course/student/{studentId}', '\\' . CourseGradeController::class . '@studentGrades');
    });

    // --- Noticias / News ---
    Route::prefix('news')->group(function () {
        Route::get('/student/{studentId}', '\\' . NewsController::class . '@studentNews');
        Route::get('/student/{studentId}/summary', '\\' . NewsController::class . '@newsSummary');
        Route::post('/student/{studentId}/{newsId}/read', '\\' . NewsController::class . '@markAsRead');
    });

    // --- Asistencia / Attendance ---
    Route::prefix('attendance')->group(function () {
        Route::get('/student/{studentId}/summary', '\\' . AttendanceController::class . '@summary');
        Route::get('/student/{studentId}/subjects', '\\' . AttendanceController::class . '@subjectsAttendance');
        Route::get('/student/{studentId}/subject/{subjectId}', '\\' . AttendanceController::class . '@subjectDetail');
    });

    // --- Inscripciones / Enrollments ---
    Route::prefix('inscripciones')->group(function () {
        Route::get('/disponibles', '\\' . InscripcionController::class . '@disponibles');
        Route::post('/inscribir', '\\' . InscripcionController::class . '@inscribir');
        Route::post('/baja', '\\' . InscripcionController::class . '@darDeBaja');
    });

    // --- Dashboard ---
    Route::prefix('dashboard')->group(function () {
        Route::get('/', '\\' . DashboardController::class . '@getStudentDashboard');
    });

    // --- Documentación / Documentation ---
    Route::prefix('documentation')->group(function () {
        Route::get('/student/{studentId}', '\\' . DocumentationController::class . '@studentDocumentation');
    });

    // Módulo de Test Virtuales
    Route::prefix('virtual-tests')->group(function () {
        Route::get('/student/{studentId}', '\\' . VirtualTestController::class . '@index');
        Route::get('/{testId}/student/{studentId}', '\\' . VirtualTestController::class . '@show');
    });

    Route::prefix('correlativities')->group(function () {
        Route::get('/student/{studentId}', '\\' . CorrelativityController::class . '@index');
    });

    Route::prefix('exam-sessions')->group(function () {
        Route::get('/student/{studentId}', '\\' . ExamSessionController::class . '@index');
    });

    Route::prefix('certificates')->group(function () {
        Route::get('/student/{studentId}', '\\' . CertificateController::class . '@index');
        Route::get('/student/{studentId}/create', '\\' . CertificateController::class . '@create');
        Route::post('/student/{studentId}', '\\' . CertificateController::class . '@store');
    });

    Route::prefix('posts')->group(function () {
        Route::get('/student/{studentId}', '\\' . PostController::class . '@index');
        Route::get('/{postId}/student/{studentId}', '\\' . PostController::class . '@show');
        Route::post('/{postId}/student/{studentId}/read', '\\' . PostController::class . '@markAsRead');
    });

    Route::prefix('profile')->group(function () {
        Route::get('/student/{studentId}', '\\' . ProfileController::class . '@show');
        Route::put('/student/{studentId}', '\\' . ProfileController::class . '@update');
    });

    Route::prefix('messages')->group(function () {
        Route::get('/student/{studentId}/recipients', '\\' . MessageController::class . '@create');
        Route::get('/student/{studentId}/chat/{codigo}', '\\' . MessageController::class . '@show');
        Route::post('/student/{studentId}', '\\' . MessageController::class . '@store');
        Route::post('/student/{studentId}/chat/{codigo}', '\\' . MessageController::class . '@reply');
    });

    /*
    |--------------------------------------------------------------------------
    | App Familias Routes
    |--------------------------------------------------------------------------
    | Quitamos el namespace('AppFamilias') porque ahora usamos rutas absolutas.
    */
    Route::prefix('app-familias')->group(function () {
        
        Route::get('/linked-students', '\\' . FamilyStudentController::class . '@index');
        Route::get('/dashboard/student/{studentId}', '\\' . FamilyDashboardController::class . '@show');
        Route::get('/agenda/student/{studentId}', '\\' . AgendaController::class . '@index');
        Route::get('/intensification/student/{studentId}', '\\' . IntensificationController::class . '@index');
        
        // MUROS DE NOVEDADES
        Route::get('/walls/student/{studentId}', '\\' . FamilyWallController::class . '@index');
        Route::get('/walls/{wallId}/student/{studentId}', '\\' . FamilyWallController::class . '@show');
        Route::post('/walls/{wallId}/student/{studentId}/intervenir', '\\' . FamilyWallController::class . '@storeIntervention');

        // LIBRO DE CALIFICACIONES (Usando el Alias)
        Route::get('/grades/student/{studentId}', '\\' . FamilyGradeController::class . '@index');

        // DETALLE DE INASISTENCIAS (Usando el Alias)
        Route::get('/attendance/student/{studentId}', '\\' . FamilyAttendanceController::class . '@index');

        // INFORMES PEDAGÓGICOS
        Route::get('/reports/student/{studentId}', '\\' . ReportController::class . '@index');

        // COMUNICADOS / POSTS
        Route::get('/posts/student/{studentId}', '\\' . FamilyPostController::class . '@index');
        Route::get('/posts/{postId}/student/{studentId}', '\\' . FamilyPostController::class . '@show');

        // COMUNICACIONES
        Route::get('/announcements/student/{studentId}', '\\' . AnnouncementController::class . '@index');
        Route::get('/announcements/{tipo}/{code}/student/{studentId}', '\\' . AnnouncementController::class . '@show');

        // AUTORIZACIONES DE RETIRO
        Route::get('/authorizations/student/{studentId}', '\\' . AuthorizationController::class . '@index');
        Route::post('/authorizations/person/student/{studentId}', '\\' . AuthorizationController::class . '@storePerson');
        Route::delete('/authorizations/person/{id}', '\\' . AuthorizationController::class . '@destroyPerson');
        Route::post('/authorizations/notice/student/{studentId}', '\\' . AuthorizationController::class . '@storeNotice');
        Route::delete('/authorizations/notice/{id}', '\\' . AuthorizationController::class . '@destroyNotice');

        // MESAS DE EXAMEN
        Route::get('/exam-boards/student/{studentId}', '\\' . ExamBoardController::class . '@index');

        // // AUTO-INSCRIPCIONES A MATERIAS GRUPALES
        // Route::get('/enrollments/student/{studentId}', '\\' . EnrollmentController::class . '@index');
        // Route::post('/enrollments/student/{studentId}', '\\' . EnrollmentController::class . '@store');
        
        // AULAS VIRTUALES (Nivel Secundario y Superior)
        Route::get('/students/{studentId}/aulas', '\\' . FamilyAulaController::class . '@index');
        Route::get('/students/{studentId}/aulas/{materiaId}/tipo/{tipoMateria}', '\\' . FamilyAulaController::class . '@show');
        Route::get('/students/{studentId}/aulas/{materiaId}/tipo/{tipoMateria}/tareas', '\\' . FamilyAulaController::class . '@tareas');
        Route::get('/students/{studentId}/aulas/{materiaId}/tipo/{tipoMateria}/recursos', '\\' . FamilyAulaController::class . '@recursos');

        // TAREAS VIRTUALES
        Route::prefix('v1/families')->group(function () {
            Route::get('students/{studentId}/tasks', '\\' . FamilyTaskController::class . '@index');
            Route::get('students/{studentId}/tasks/{taskId}', '\\' . FamilyTaskController::class . '@show');
            Route::post('students/{studentId}/tasks/{taskId}/resolutions', '\\' . FamilyTaskController::class . '@storeResolution');
            Route::post('students/{studentId}/tasks/{taskId}/queries', '\\' . FamilyTaskController::class . '@storeQuery');
            Route::get('students/{studentId}/aulas-superior', '\\' . FamilyAulaController::class . '@index');
        });

        // PERFIL DEL ALUMNO (Usando el Alias)
        Route::get('/profile/student/{studentId}', '\\' . FamilyProfileController::class . '@show');
        Route::post('/profile/student/{studentId}/update', '\\' . FamilyProfileController::class . '@update');
        Route::post('/profile/student/{studentId}/photo', '\\' . FamilyProfileController::class . '@updatePhoto');

        // MENSAJERÍA BIDIRECCIONAL
        Route::get('/messaging/student/{studentId}', '\\' . MessagingController::class . '@index');
        Route::get('/messaging/chat/{code}', '\\' . MessagingController::class . '@show');
        Route::get('/messaging/recipients/student/{studentId}', '\\' . MessagingController::class . '@recipients');
        Route::post('/messaging/send', '\\' . MessagingController::class . '@store');
    });
});