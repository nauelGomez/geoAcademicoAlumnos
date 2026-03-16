<?php

namespace App\Http\Controllers;

use App\Repositories\CourseGradeRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CourseGradeController extends Controller
{
    protected $courseGradeRepo;

    public function __construct(CourseGradeRepository $courseGradeRepo)
    {
        $this->courseGradeRepo = $courseGradeRepo;
    }

    public function studentGrades(int $studentId): JsonResponse
    {
        try {
            // Renombramos el método también para mantener la facha en inglés
            $data = $this->courseGradeRepo->getCourseGradesEvolution($studentId);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el alumno o no tiene un plan de estudio asignado.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $data,
                'message' => 'Course grades loaded de nashe.'
            ], 200);

        } catch (Exception $e) {
            Log::error('Error cargando notas cursadas: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al cargar las notas.',
                'error'   => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }
}