<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\FamilyAulaRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;

class FamilyAulaController extends BaseInstitutionController
{
    /** @var FamilyAulaRepository */
    protected $repo;

    public function __construct(FamilyAulaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request, int $studentId): JsonResponse
    {
        $request->validate([
            'ciclo' => 'nullable|integer|min:2000|max:2100',
        ]);

        try {
            $cicloLectivo = $request->query('ciclo');
            $data = $this->repo->getAulasDisponibles($studentId, $cicloLectivo);

            if (is_null($data)) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Alumno o ciclo no encontrado.',
                    'errors' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Aulas virtuales obtenidas correctamente.',
                'errors' => null,
            ], 200);
        } catch (Throwable $e) {
            \Log::error('Error FamilyAulaController@index', [
                'student_id' => $studentId,
                'msg' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Error al cargar las aulas virtuales.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function show(Request $request, int $studentId, int $materiaId, string $tipoMateria): JsonResponse
    {
        $request->validate([
            'ciclo' => 'nullable|integer|min:2000|max:2100',
        ]);

        if (!in_array(strtolower($tipoMateria), ['c', 'g'], true)) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Tipo de materia inválido.',
                'errors' => ['El tipo_materia debe ser c o g.'],
            ], 422);
        }

        try {
            $cicloLectivo = $request->query('ciclo');
            $data = $this->repo->getDetalleAula($studentId, $materiaId, $tipoMateria, $cicloLectivo);

            if (is_null($data)) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Alumno, ciclo o aula no encontrada.',
                    'errors' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Contenido del aula obtenido correctamente.',
                'errors' => null,
            ], 200);
        } catch (Throwable $e) {
            \Log::error('Error FamilyAulaController@show', [
                'student_id' => $studentId,
                'materia_id' => $materiaId,
                'tipo_materia' => $tipoMateria,
                'msg' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Error al cargar el contenido del aula.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function tareas(Request $request, int $studentId, int $materiaId, string $tipoMateria): JsonResponse
    {
        $request->validate([
            'ciclo' => 'nullable|integer|min:2000|max:2100',
        ]);

        if (!in_array(strtolower($tipoMateria), ['c', 'g'], true)) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Tipo de materia inválido.',
                'errors' => ['El tipo_materia debe ser c o g.'],
            ], 422);
        }

        try {
            $cicloLectivo = $request->query('ciclo');
            $data = $this->repo->getTareasGenerales($studentId, $materiaId, $tipoMateria, $cicloLectivo);

            if (is_null($data)) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Alumno o ciclo no encontrado.',
                    'errors' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Tareas generales obtenidas correctamente.',
                'errors' => null,
            ], 200);
        } catch (Throwable $e) {
            \Log::error('Error FamilyAulaController@tareas', [
                'student_id' => $studentId,
                'materia_id' => $materiaId,
                'tipo_materia' => $tipoMateria,
                'msg' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Error al cargar las tareas generales.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function recursos(Request $request, int $studentId, int $materiaId, string $tipoMateria): JsonResponse
    {
        $request->validate([
            'ciclo' => 'nullable|integer|min:2000|max:2100',
        ]);

        if (!in_array(strtolower($tipoMateria), ['c', 'g'], true)) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Tipo de materia inválido.',
                'errors' => ['El tipo_materia debe ser c o g.'],
            ], 422);
        }

        try {
            $cicloLectivo = $request->query('ciclo');
            $data = $this->repo->getRecursosGenerales($studentId, $materiaId, $tipoMateria, $cicloLectivo);

            if (is_null($data)) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Alumno o ciclo no encontrado.',
                    'errors' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Recursos generales obtenidos correctamente.',
                'errors' => null,
            ], 200);
        } catch (Throwable $e) {
            \Log::error('Error FamilyAulaController@recursos', [
                'student_id' => $studentId,
                'materia_id' => $materiaId,
                'tipo_materia' => $tipoMateria,
                'msg' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Error al cargar los recursos generales.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }
}
