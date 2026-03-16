<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardRequest;
use App\Repositories\DashboardRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected $dashboardRepo;

    public function __construct(DashboardRepository $dashboardRepo)
    {
        $this->dashboardRepo = $dashboardRepo;
    }

    public function getStudentDashboard(DashboardRequest $request): JsonResponse
    {
        try {
            // Ya no dependemos de auth()->user() ni de session()
            $alumnoId = $request->input('alumno_id');
            
            // Si el legacy dependía estrictamente del mail del responsable logueado para filtrar tareas, 
            // ahora podés pasarlo por query string. Si no viene, pasamos un string vacío o un default.
            $emailResponsable = $request->input('mail', ''); 
            
            $data = $this->dashboardRepo->getDashboardMetrics($alumnoId, $emailResponsable);

            return response()->json([
                'success' => true,
                'data'    => $data,
                'message' => 'Dashboard cargado correctamente.'
            ], 200);

        } catch (Exception $e) {
            // Guardamos el error real en los logs de Laravel para poder debuggear
            Log::error('Error cargando dashboard: ' . $e->getMessage() . ' en la línea ' . $e->getLine() . ' de ' . $e->getFile());

            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Ocurrió un error en el servidor.',
                'error'   => env('APP_DEBUG') ? $e->getMessage() : 'Error interno' // Solo muestra el error real si APP_DEBUG=true
            ], 500);
        }
    }
}