<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Http\Requests\AppFamilias\ResolverTareaFamiliaRequest;
use App\Repositories\AppFamilias\FamilyAulaRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use RuntimeException;
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

    public function detalleTarea(Request $request, $studentId, $materiaId, $tipoMateria, $taskId): JsonResponse
    {
        try {
            $cicloLectivo = $request->query('ciclo');
            $data = $this->repo->getDetalleTarea($studentId, $materiaId, $tipoMateria, $taskId, $cicloLectivo);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Tarea no encontrada.',
                    'errors' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Detalle de tarea obtenido correctamente.',
                'errors' => null,
            ], 200);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $e->getMessage(),
                'errors' => null,
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Error FamilyAulaController@detalleTarea', [
                'student_id' => (int) $studentId,
                'materia_id' => (int) $materiaId,
                'tipo_materia' => (string) $tipoMateria,
                'task_id' => (int) $taskId,
                'msg' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Error al obtener el detalle de la tarea.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function resolverTarea(
        ResolverTareaFamiliaRequest $request,
        $studentId,
        $materiaId,
        $tipoMateria,
        $taskId
    ): JsonResponse {
        try {
            $data = $this->repo->guardarResolucionTarea(
                (int) $studentId,
                (int) $materiaId,
                (string) $tipoMateria,
                (int) $taskId,
                $request->header('X-Institution-ID'),
                $request->validated(),
                $request->file('archivos', [])
            );

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Resolución guardada correctamente.',
                'errors' => null,
            ], 200);
        } catch (RuntimeException $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $e->getMessage(),
                'errors' => null,
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Error FamilyAulaController@resolverTarea', [
                'student_id' => (int) $studentId,
                'materia_id' => (int) $materiaId,
                'tipo_materia' => (string) $tipoMateria,
                'task_id' => (int) $taskId,
                'msg' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Error al guardar la resolución de la tarea.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }
}
