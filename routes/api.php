<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Helper para IDE (Ctrl+Click) compatible con Laravel 5.5
|--------------------------------------------------------------------------
| Este helper engaña al IDE para que reconozca el array como un callable
| (habilitando el Ctrl+Click en el método), pero le entrega a Laravel 5.5
| el string exacto que necesita para que `php artisan route:cache` no se rompa.
*/

if (!function_exists('ide_route')) {
    function ide_route(array $callable)
    {
        return '\\' . $callable[0] . '@' . $callable[1];
    }
}

/*
|--------------------------------------------------------------------------
| Importación de Controladores
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

// Controladores de AppFamilias
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
    Route::get('/', ide_route([InstitutionController::class, 'index']));
    Route::get('/{institutionId}', ide_route([InstitutionController::class, 'show']));
});

/*
|--------------------------------------------------------------------------
| Multitenant Routes (Requieren Header X-Institution-ID)
|--------------------------------------------------------------------------
*/
Route::middleware(['tenant'])->group(function () {

    // --- Alumnos ---
    Route::prefix('alumnos')->group(function () {
        Route::get('/', ide_route([AlumnoController::class, 'index']));
        Route::get('/{studentId}', ide_route([AlumnoController::class, 'show']));
    });

    // --- Tareas / Tasks ---
    Route::prefix('tasks')->group(function () {
        Route::get('/', ide_route([TaskController::class, 'index']));
        Route::get('/stats', ide_route([TaskController::class, 'getTaskStats']));
        Route::get('/{taskId}', ide_route([TaskController::class, 'show']));
        Route::get('/student/{studentId}', ide_route([TaskController::class, 'studentTasks']));
        Route::post('/resolution', ide_route([TaskController::class, 'submitResolution']));
        Route::get('/{studentId}/{materiaId}/{tipoMateria}/tarea/{taskId}', ide_route([FamilyAulaController::class, 'detalleTarea']));
        Route::post('/{studentId}/{materiaId}/{tipoMateria}/tarea/{taskId}/resolver', ide_route([FamilyAulaController::class, 'resolverTarea']));
    });

    Route::prefix('foros')->group(function () {
        Route::get('/{studentId}/{materiaId}/{tipoMateria}/clase/{classId}/foros', [FamilyAulaController::class, 'listarForosClase']);
        Route::get('/{studentId}/{forumId}', [FamilyAulaController::class, 'detalleForo']);
        Route::get('/{studentId}/{forumId}/intervenciones/listado', [FamilyAulaController::class, 'listarForoPaginado']);
        Route::post('/{studentId}/{forumId}/intervenciones/', [FamilyAulaController::class, 'enviarIntervencionForo']);
    });


    Route::prefix('clases')->group(function () {
        Route::get('{studentId}/{materiaId}/{tipoMateria}/clase/{classId}/detalle/{id}', [FamilyAulaController::class, 'detalleClase']);
        Route::get('{studentId}/{materiaId}/{tipoMateria}/clase/{classId}/contenidos/{id}', [FamilyAulaController::class, 'listarContenidosClase']);
    });



    // --- Calificaciones / Grades ---
    Route::prefix('grades')->group(function () {
        Route::get('/student/{studentId}', ide_route([GradeController::class, 'studentGrades']));
        Route::get('/student/{studentId}/summary', ide_route([GradeController::class, 'gradesSummary']));

        // --- Course Grades ---
        Route::get('/course/student/{studentId}', ide_route([CourseGradeController::class, 'studentGrades']));
    });

    // --- Noticias / News ---
    Route::prefix('news')->group(function () {
        Route::get('/student/{studentId}', ide_route([NewsController::class, 'studentNews']));
        Route::get('/student/{studentId}/summary', ide_route([NewsController::class, 'newsSummary']));
        Route::post('/student/{studentId}/{newsId}/read', ide_route([NewsController::class, 'markAsRead']));
    });

    // --- Asistencia / Attendance ---
    Route::prefix('attendance')->group(function () {
        Route::get('/student/{studentId}/summary', ide_route([AttendanceController::class, 'summary']));
        Route::get('/student/{studentId}/subjects', ide_route([AttendanceController::class, 'subjectsAttendance']));
        Route::get('/student/{studentId}/subject/{subjectId}', ide_route([AttendanceController::class, 'subjectDetail']));
    });

    // --- Inscripciones / Enrollments ---
    Route::prefix('inscripciones')->group(function () {
        Route::get('/disponibles', ide_route([InscripcionController::class, 'disponibles']));
        Route::post('/inscribir', ide_route([InscripcionController::class, 'inscribir']));
        Route::post('/baja', ide_route([InscripcionController::class, 'darDeBaja']));
    });

    // --- Dashboard ---
    Route::prefix('dashboard')->group(function () {
        Route::get('/', ide_route([DashboardController::class, 'getStudentDashboard']));
    });

    // --- Documentación / Documentation ---
    Route::prefix('documentation')->group(function () {
        Route::get('/student/{studentId}', ide_route([DocumentationController::class, 'studentDocumentation']));
    });

    // Módulo de Test Virtuales
    Route::prefix('virtual-tests')->group(function () {
        Route::get('/student/{studentId}', ide_route([VirtualTestController::class, 'index']));
        Route::get('/{testId}/student/{studentId}', ide_route([VirtualTestController::class, 'show']));
    });

    Route::prefix('correlativities')->group(function () {
        Route::get('/student/{studentId}', ide_route([CorrelativityController::class, 'index']));
    });

    Route::prefix('exam-sessions')->group(function () {
        Route::get('/student/{studentId}', ide_route([ExamSessionController::class, 'index']));
    });

    Route::prefix('certificates')->group(function () {
        Route::get('/student/{studentId}', ide_route([CertificateController::class, 'index']));
        Route::get('/student/{studentId}/create', ide_route([CertificateController::class, 'create']));
        Route::post('/student/{studentId}', ide_route([CertificateController::class, 'store']));
    });

    Route::prefix('posts')->group(function () {
        Route::get('/student/{studentId}', ide_route([PostController::class, 'index']));
        Route::get('/{postId}/student/{studentId}', ide_route([PostController::class, 'show']));
        Route::post('/{postId}/student/{studentId}/read', ide_route([PostController::class, 'markAsRead']));
    });

    Route::prefix('profile')->group(function () {
        Route::get('/student/{studentId}', ide_route([ProfileController::class, 'show']));
        Route::put('/student/{studentId}', ide_route([ProfileController::class, 'update']));
    });

// --- Mensajería / Messaging (Unificado) ---
    Route::prefix('messages')->group(function () {
        // 1. Listado de conversaciones por alumno
        Route::get('/conversations/{studentId}', ide_route([MessageController::class, 'index']));

        // 2. Obtener destinatarios habilitados
        Route::get('/recipients/{studentId}', ide_route([MessageController::class, 'recipients']));

        // 3. Ver detalle de un chat
        Route::get('/chat/{codigo}', ide_route([MessageController::class, 'show']));

        // 4. Iniciar conversación o responder
        Route::post('/send', ide_route([MessageController::class, 'store']));
    });
    /*
    |--------------------------------------------------------------------------
    | App Familias Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('app-familias')->group(function () {

        Route::get('/linked-students', ide_route([FamilyStudentController::class, 'index']));
        Route::get('/dashboard/student/{studentId}', ide_route([FamilyDashboardController::class, 'show']));
        Route::get('/agenda/student/{studentId}', ide_route([AgendaController::class, 'index']));
        Route::get('/intensification/student/{studentId}', ide_route([IntensificationController::class, 'index']));

        // MUROS DE NOVEDADES
        Route::get('/walls/student/{studentId}', ide_route([FamilyWallController::class, 'index']));
        Route::get('/walls/{wallId}/student/{studentId}', ide_route([FamilyWallController::class, 'show']));
        Route::post('/walls/{wallId}/student/{studentId}/intervenir', ide_route([FamilyWallController::class, 'storeIntervention']));

        // LIBRO DE CALIFICACIONES
        Route::get('/grades/student/{studentId}', ide_route([FamilyGradeController::class, 'index']));

        // DETALLE DE INASISTENCIAS
        Route::get('/attendance/student/{studentId}', ide_route([FamilyAttendanceController::class, 'index']));

        // INFORMES PEDAGÓGICOS
        Route::get('/reports/student/{studentId}', ide_route([ReportController::class, 'index']));

        // COMUNICADOS / POSTS
        Route::get('/posts/student/{studentId}', ide_route([FamilyPostController::class, 'index']));
        Route::get('/posts/{postId}/student/{studentId}', ide_route([FamilyPostController::class, 'show']));

        // COMUNICACIONES
        Route::get('/announcements/student/{studentId}', ide_route([AnnouncementController::class, 'index']));
        Route::get('/announcements/{tipo}/{code}/student/{studentId}', ide_route([AnnouncementController::class, 'show']));

        // AUTORIZACIONES DE RETIRO
        Route::get('/authorizations/student/{studentId}', ide_route([AuthorizationController::class, 'index']));
        Route::post('/authorizations/person/student/{studentId}', ide_route([AuthorizationController::class, 'storePerson']));
        Route::delete('/authorizations/person/{id}', ide_route([AuthorizationController::class, 'destroyPerson']));
        Route::post('/authorizations/notice/student/{studentId}', ide_route([AuthorizationController::class, 'storeNotice']));
        Route::delete('/authorizations/notice/{id}', ide_route([AuthorizationController::class, 'destroyNotice']));

        // MESAS DE EXAMEN
        Route::get('/exam-boards/student/{studentId}', ide_route([ExamBoardController::class, 'index']));

        // AULAS VIRTUALES
        Route::get('/students/{studentId}/aulas', ide_route([FamilyAulaController::class, 'index']));
        Route::get('/students/{studentId}/aulas/{materiaId}/tipo/{tipoMateria}', ide_route([FamilyAulaController::class, 'show']));
        Route::get('/students/{studentId}/aulas/{materiaId}/tipo/{tipoMateria}/tareas', ide_route([FamilyAulaController::class, 'tareas']));
        Route::get('/students/{studentId}/aulas/{materiaId}/tipo/{tipoMateria}/recursos', ide_route([FamilyAulaController::class, 'recursos']));
        Route::get('/students/{studentId}/aulas/{materiaId}/tipo/{tipoMateria}/clases', ide_route([FamilyAulaController::class, 'clases']));

        // TAREAS VIRTUALES
        Route::prefix('v1/families')->group(function () {
            Route::get('students/{studentId}/tasks', ide_route([FamilyTaskController::class, 'index']));
            Route::get('students/{studentId}/tasks/{taskId}', ide_route([FamilyTaskController::class, 'show']));
            Route::post('students/{studentId}/tasks/{taskId}/resolutions', ide_route([FamilyTaskController::class, 'storeResolution']));
            Route::post('students/{studentId}/tasks/{taskId}/queries', ide_route([FamilyTaskController::class, 'storeQuery']));
            Route::get('students/{studentId}/aulas-superior', ide_route([FamilyAulaController::class, 'index']));
        });

        // PERFIL DEL ALUMNO
        Route::get('/profile/student/{studentId}', ide_route([FamilyProfileController::class, 'show']));
        Route::post('/profile/student/{studentId}/update', ide_route([FamilyProfileController::class, 'update']));
        Route::post('/profile/student/{studentId}/photo', ide_route([FamilyProfileController::class, 'updatePhoto']));

        // MENSAJERÍA BIDIRECCIONAL
        Route::get('/messaging/student/{studentId}', ide_route([MessagingController::class, 'index']));
        Route::get('/messaging/chat/{code}', ide_route([MessagingController::class, 'show']));
        Route::get('/messaging/recipients/student/{studentId}', ide_route([MessagingController::class, 'recipients']));
        Route::post('/messaging/send', ide_route([MessagingController::class, 'store']));
    });
});
