<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Repositories\GradeRepository;
use Exception;
use Illuminate\Support\Facades\Log;

class GradeController extends Controller // <--- Hereda del Controller normal
{
    protected $gradeRepository;

    public function __construct(GradeRepository $gradeRepository)
    {
        $this->gradeRepository = $gradeRepository;
    }

    /**
     * Evolución de Cursada
     */
    public function studentGrades(Request $request, $studentId): JsonResponse
    {
        try {
            // Ya no validamos el tenant acá, asumimos que el Middleware ya lo hizo y seteó la BD.
            $data = $this->gradeRepository->getEvolucionCursada($studentId);

            return response()->json([
                'status' => 'success',
                'data' => $data
            ], 200);

        } catch (Exception $e) {
            Log::error('Error fetching student grades: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching student grades: ' . $e->getMessage(),
                'debug_error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function gradesSummary(Request $request, $studentId): JsonResponse
    {
        try {
            // TODO: Crear método getSummary() en el GradeRepository cuando lo necesites.
            return response()->json([
                'status' => 'success',
                'data' => [
                    'mensaje' => 'Endpoint de summary pendiente de implementar dinámicamente.'
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Error fetching grades summary: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching grades summary: ' . $e->getMessage()
            ], 500);
        }
    }
}