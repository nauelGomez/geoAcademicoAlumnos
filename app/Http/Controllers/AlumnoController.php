<?php

namespace App\Http\Controllers;

use App\Repositories\AlumnoRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class AlumnoController extends Controller
{
    /** @var AlumnoRepository */
    private $alumnoRepository;

    public function __construct(AlumnoRepository $alumnoRepository)
    {
        $this->alumnoRepository = $alumnoRepository;
    }

    /**
     * Obtiene el listado de alumnos.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // El Middleware 'tenant' ya configuró la DB. Solo llamamos al repo.
            $alumnos = $this->alumnoRepository->getAlumnosPaginados(50);
            
            return response()->json([
                'status'         => 'success',
                'data'           => $alumnos,
                'count'          => $alumnos->count(),
                'institution_id' => $request->header('X-Institution-ID') // Lo leemos directo del header
            ], 200);
            
        } catch (Exception $e) {
            \Log::error('Error fetching students: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al obtener alumnos.'
            ], 500);
        }
    }

    /**
     * Muestra un alumno en específico.
     */
    public function show(Request $request, $studentId): JsonResponse
    {
        try {
            $student = $this->alumnoRepository->getAlumnoPorId($studentId);
            
            if (!$student) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Alumno no encontrado'
                ], 404);
            }
            
            return response()->json([
                'status'         => 'success',
                'data'           => $student,
                'institution_id' => $request->header('X-Institution-ID')
            ], 200);
            
        } catch (Exception $e) {
            \Log::error('Error fetching student: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al obtener alumno.'
            ], 500);
        }
    }
}