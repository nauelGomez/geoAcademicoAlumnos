<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\FamilyAulaRepository;
use Illuminate\Http\Request;

class FamilyAulaController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(FamilyAulaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request, $studentId)
    {
        try {
            // Agarramos el ciclo del request, o por defecto usamos el año actual
            $cicloLectivo = $request->query('ciclo', date('Y')); 

            $data = $this->repo->getAulasDisponibles($studentId, $cicloLectivo);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las aulas virtuales',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $studentId, $materiaId, $tipoMateria)
    {
        try {
            $cicloLectivo = $request->query('ciclo', date('Y'));
            $data = $this->repo->getDetalleAula($studentId, $materiaId, $tipoMateria, $cicloLectivo);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el contenido del aula',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    public function tareas(Request $request, $studentId, $materiaId, $tipoMateria)
    {
        try {
            $cicloLectivo = $request->query('ciclo', date('Y'));
            $data = $this->repo->getTareasGenerales($studentId, $materiaId, $tipoMateria, $cicloLectivo);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las tareas generales',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    public function recursos(Request $request, $studentId, $materiaId, $tipoMateria)
    {
        try {
            $cicloLectivo = $request->query('ciclo', date('Y'));
            $data = $this->repo->getRecursosGenerales($studentId, $materiaId, $tipoMateria, $cicloLectivo);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los recursos generales',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}