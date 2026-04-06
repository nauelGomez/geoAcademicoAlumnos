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

    public function listarForosClase(Request $request, $studentId, $materiaId, $tipoMateria, $classId): JsonResponse
    {
        $request->validate([
            'ciclo' => 'nullable|integer|min:2000|max:2100',
        ]);

        try {
            $cicloLectivo = $request->query('ciclo');
            $data = $this->repo->listarForosClaseAlumno(
                (int) $studentId,
                (int) $materiaId,
                (string) $tipoMateria,
                (int) $classId,
                $cicloLectivo
            );

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Clase o foros no encontrados.',
                    'errors' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Foros de la clase obtenidos correctamente.',
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
            \Log::error('Error FamilyAulaController@listarForosClase', [
                'student_id' => (int) $studentId,
                'materia_id' => (int) $materiaId,
                'tipo_materia' => (string) $tipoMateria,
                'class_id' => (int) $classId,
                'msg' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Error al obtener los foros de la clase.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function detalleForo(Request $request, $studentId, $forumId): JsonResponse
    {
        try {
            $data = $this->repo->detalleForoAlumno(
                (int) $studentId,
                (int) $forumId,
                (int) $request->header('X-Institution-ID'));

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Foro no encontrado.',
                    'errors' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Detalle de foro obtenido correctamente.',
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
            \Log::error('Error FamilyAulaController@detalleForo', [
                'student_id' => (int) $studentId,
                'forum_id' => (int) $forumId,
                'msg' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Error al obtener el detalle del foro.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function listarForoPaginado(Request $request, $studentId, $forumId): JsonResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'id_respuesta' => 'nullable|integer|min:0',
            'order' => 'nullable|string|in:ASC,DESC,asc,desc',
        ]);

        try {
            $data = $this->repo->listarForoAlumnoPaginado(
                (int) $studentId,
                (int) $forumId,
                (int)  $request->header('X-Institution-ID'),
                (int) $request->query('per_page', 50),
                (int) $request->query('id_respuesta', 0),
                (string) $request->query('order', 'DESC')
            );

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Intervenciones del foro obtenidas correctamente.',
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
            \Log::error('Error FamilyAulaController@listarForoPaginado', [
                'student_id' => (int) $studentId,
                'forum_id' => (int) $forumId,
                'msg' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Error al obtener las intervenciones del foro.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }

    public function enviarIntervencionForo(Request $request, $studentId, $forumId, $id): JsonResponse
    {
        $request->validate([
            'mensaje' => 'required|string',
            'id_respuesta' => 'nullable|integer|min:0',
            'adjuntos' => 'nullable|array',
            'adjuntos.*' => 'file|max:15360|mimes:png,jpg,jpeg,webp,doc,docx,xls,xlsx,pdf,ppt,pptx,mp3,mp4,mov,zip',
        ]);

        try {
            $data = $this->repo->enviarIntervencionForoAlumno(
                (int) $studentId,
                (int) $forumId,
                (int) $id,
                [
                    'mensaje' => (string) $request->input('mensaje'),
                    'id_respuesta' => (int) $request->input('id_respuesta', 0),
                ],
                $request->file('adjuntos', [])
            );

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Intervención enviada correctamente.',
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
            \Log::error('Error FamilyAulaController@enviarIntervencionForo', [
                'student_id' => (int) $studentId,
                'forum_id' => (int) $forumId,
                'msg' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Error al enviar la intervención del foro.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }
}
