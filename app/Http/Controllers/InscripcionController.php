<?php

namespace App\Http\Controllers;

use App\Http\Requests\InscribirMateriaRequest;
use App\Repositories\InscripcionRepository;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class InscripcionController extends Controller
{
    // Usamos el nombre original que tenías
    protected $inscripcionRepo;

    public function __construct(InscripcionRepository $inscripcionRepo)
    {
        $this->inscripcionRepo = $inscripcionRepo;
    }

    public function disponibles(Request $request)
    {
        try {
            $alumnoId = session('idal', $request->header('X-Alumno-ID')); 

            if (!$alumnoId) {
                return response()->json(['success' => false, 'message' => 'Sesión de alumno no válida'], 401);
            }

            // Llamada correcta a la propiedad y al método
            $resultado = $this->inscripcionRepo->getMateriasDisponibles((int) $alumnoId);

            if (isset($resultado['status']) && $resultado['status'] === 'error') {
                return response()->json(['success' => false, 'message' => $resultado['message']], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $resultado['data'],
                'message' => 'Grupos disponibles obtenidos correctamente.'
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al obtener cursos disponibles: ' . $e->getMessage());
            
            // Modo Debug encendido para cazar cualquier otro error SQL
            return response()->json([
                'success' => false, 
                'message' => 'Error de código o SQL',
                'debug_error' => $e->getMessage(),
                'debug_line' => $e->getLine(),
                'debug_file' => basename($e->getFile())
            ], 500);
        }
    }

    public function inscribir(InscribirMateriaRequest $request)
    {
        try {
            $alumnoId = session('idal', $request->header('X-Alumno-ID'));

            $this->inscripcionRepo->inscribirAlumno((int) $alumnoId, $request->id_materia_grupal);

            return response()->json([
                'success' => true,
                'message' => 'Inscripción realizada con éxito.'
            ], 201);

        } catch (Exception $e) {
            Log::warning("Fallo al inscribir alumno {$alumnoId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}