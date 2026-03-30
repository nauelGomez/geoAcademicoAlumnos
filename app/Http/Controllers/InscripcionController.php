<?php

namespace App\Http\Controllers;

use App\Http\Requests\GestionarInscripcionRequest;
use App\Repositories\InscripcionRepository;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class InscripcionController extends Controller
{
    protected $inscripcionRepo;

    public function __construct(InscripcionRepository $inscripcionRepo)
    {
        $this->inscripcionRepo = $inscripcionRepo;
    }

    public function disponibles(Request $request)
    {
        try {
            $alumnoId = session('idal', $request->header('X-Alumno-ID')); 
            $instId = $request->header('X-Institution-ID'); 

            if (!$alumnoId || !$instId) {
                return response()->json(['success' => false, 'message' => 'Sesión de alumno o institución no válida'], 401);
            }

            $resultado = $this->inscripcionRepo->getMateriasDisponibles((int) $instId, (int) $alumnoId);

            if (isset($resultado['status']) && $resultado['status'] === 'error') {
                return response()->json(['success' => false, 'message' => $resultado['message']], 400);
            }

            // --- RESPUESTA CON EL NUEVO CAMPO ---
            return response()->json([
                'success' => true,
                'data' => $resultado['data'],
                'insc_disponibles' => $resultado['insc_disponibles'] ?? 0, // <-- ACÁ APARECE EN EL JSON FINAL
                'message' => 'Grupos disponibles obtenidos correctamente.'
            ], 200);

        } catch (Exception $e) {
            Log::error('Error al obtener cursos disponibles: ' . $e->getMessage());
            
            return response()->json([
                'success' => false, 
                'message' => 'Error de código o SQL',
                'debug_error' => $e->getMessage()
            ], 500);
        }
    }
    public function inscribir(GestionarInscripcionRequest $request)
    {
        $alumnoId = session('idal', $request->header('X-Alumno-ID'));
        $instId = $request->header('X-Institution-ID'); 

        try {
            if (!$alumnoId || !$instId) {
                return response()->json(['success' => false, 'message' => 'Sesión de alumno o institución no válida'], 401);
            }

            $this->inscripcionRepo->inscribir((int) $instId, (int) $alumnoId, $request->id_materia_grupal);

            return response()->json([
                'success' => true,
                'message' => 'Inscripción confirmada con éxito.'
            ], 201);

        } catch (\Exception $e) {
            $idLog = $alumnoId ?? 'Desconocido';
            Log::warning("Fallo al inscribir alumno {$idLog}: " . $e->getMessage());
            
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function darDeBaja(GestionarInscripcionRequest $request)
    {
        $alumnoId = session('idal', $request->header('X-Alumno-ID'));

        try {
            if (!$alumnoId) {
                return response()->json(['success' => false, 'message' => 'Sesión no válida'], 401);
            }

            $this->inscripcionRepo->darDeBaja((int) $alumnoId, $request->id_materia_grupal);

            return response()->json([
                'success' => true,
                'message' => 'La inscripción ha sido cancelada correctamente.'
            ], 200);

        } catch (\Exception $e) {
            $idLog = $alumnoId ?? 'Desconocido';
            Log::warning("Fallo al cancelar inscripción del alumno {$idLog}: " . $e->getMessage());
            
            return response()->json([
                'success' => false, 
                'message' => $e->getMessage()
            ], 422);
        }
    }
}