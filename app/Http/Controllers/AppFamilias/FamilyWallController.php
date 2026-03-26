<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\FamilyWallRepository;
use App\Http\Requests\AppFamilias\StoreWallInterventionRequest;
use Illuminate\Http\Request;

class FamilyWallController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(FamilyWallRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Obtener listado de muros disponibles para un alumno.
     *
     * @param Request $request
     * @param int $studentId ID del alumno
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $studentId)
    {
        try {
            $data = $this->repo->getWalls($studentId);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los muros de novedades',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalle del muro con todas sus intervenciones.
     * Marca automáticamente los mensajes del docente como leídos.
     *
     * @param int $wallId ID del muro
     * @param int $studentId ID del alumno
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($wallId, $studentId)
    {
        \Log::info("FamilyWallController@show - WallID: {$wallId}, StudentID: {$studentId}");
        
        try {
            $data = $this->repo->getWallDetails($wallId, $studentId);
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'El muro no existe'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el muro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar nueva intervención del alumno en el muro.
     *
     * @param StoreWallInterventionRequest $request
     * @param int $wallId ID del muro
     * @param int $studentId ID del alumno
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeIntervention(StoreWallInterventionRequest $request, $wallId, $studentId)
    {
        try {
            $intervention = $this->repo->storeIntervention($wallId, $studentId, $request->mensaje);
            
            return response()->json([
                'success' => true,
                'message' => 'Intervención publicada correctamente',
                'data' => [
                    'id' => $intervention->ID,
                    'mensaje' => $intervention->Mensaje,
                    'fecha' => $intervention->Fecha,
                    'hora' => $intervention->Hora
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo publicar la intervención',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
