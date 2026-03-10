<?php
namespace App\Http\Controllers;

use App\Http\Requests\GestionarInscripcionRequest; // <-- FIX del nombre del Request
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
            // Obtenemos la institución desde el header para decidir el repo
            $instId = $request->header('X-Institution-ID');

            if (!$alumnoId) {
                return response()->json(['success' => false, 'message' => 'Sesión de alumno no válida'], 401);
            }

            // ELEGIR REPOSITORY: 
            // Si es la 21, instanciamos el especial. Si no, usamos el que ya tiene el controller ($this->inscripcionRepo)
            $repo = ($instId == 21) 
                ? new \App\Repositories\InscripcionEutRepository() 
                : $this->inscripcionRepo;

            $resultado = $repo->getMateriasDisponibles((int) $alumnoId);

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
            
            return response()->json([
                'success' => false, 
                'message' => 'Error de código o SQL',
                'debug_error' => $e->getMessage(),
                'debug_line' => $e->getLine(),
                'debug_file' => basename($e->getFile())
            ], 500);
        }
    }

   public function inscribir(GestionarInscripcionRequest $request)
    {
        $alumnoId = session('idal', $request->header('X-Alumno-ID'));

        try {
            if (!$alumnoId) {
                return response()->json(['success' => false, 'message' => 'Sesión no válida'], 401);
            }

            // <-- FIX: Usamos inscripcionRepo como en el constructor
            $this->inscripcionRepo->inscribir((int) $alumnoId, $request->id_materia_grupal);

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

            // <-- FIX: Usamos inscripcionRepo como en el constructor
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