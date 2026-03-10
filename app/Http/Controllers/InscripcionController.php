<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\GestionarInscripcionRequest;
use Illuminate\Support\Facades\Log;
// Usá la ruta completa para evitar confusiones
use App\Repositories\InscripcionRepository;

class InscripcionController extends Controller
{
    protected $repo;

    // Cambiá la inyección para que sea explícita
    public function __construct(\App\Repositories\InscripcionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getDisponibles(Request $request)
    {
        return response()->json(['status' => 'Llegaste al Controller!']);
    }
    public function inscribir(GestionarInscripcionRequest $request)
    {
        // 1. Capturamos el ID afuera del try para que exista globalmente en el método
        $alumnoId = session('idal', $request->header('X-Alumno-ID'));

        try {
            if (!$alumnoId) {
                return response()->json(['success' => false, 'message' => 'Sesión no válida'], 401);
            }

            $this->repo->inscribir((int) $alumnoId, $request->id_materia_grupal);
            return response()->json([
                'success' => true,
                'message' => 'Inscripción confirmada con éxito.'
            ], 201);
        } catch (\Exception $e) {
            // Si $alumnoId por algún motivo es null, le ponemos un fallback para que el log no explote
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
        // 1. Capturamos el ID afuera
        $alumnoId = session('idal', $request->header('X-Alumno-ID'));

        try {
            if (!$alumnoId) {
                return response()->json(['success' => false, 'message' => 'Sesión no válida'], 401);
            }

            $this->repo->darDeBaja((int) $alumnoId, $request->id_materia_grupal);

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
