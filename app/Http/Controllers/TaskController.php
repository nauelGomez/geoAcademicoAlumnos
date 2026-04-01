<?php

namespace App\Http\Controllers;

use App\Repositories\TaskRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class TaskController extends Controller
{
    /** @var TaskRepository */
    private $taskRepository;

    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    public function index(): JsonResponse
    {
        try {
            $tasks = $this->taskRepository->getAllActiveTasks();
            
            return response()->json([
                'status' => 'success',
                'data' => $tasks,
                'count' => $tasks->count()
            ], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching tasks: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error al obtener tareas'], 500);
        }
    }

public function studentTasks(int $studentId): JsonResponse
{
    try {
        $data = $this->taskRepository->getTasksForStudent($studentId);
        
        if (!$data) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Alumno no encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $data,
            'count'  => count($data['tasks'])
        ], 200);

    } catch (\Exception $e) {
        \Log::error('Error fetching student tasks: ' . $e->getMessage());
        return response()->json([
            'status'  => 'error', 
            'message' => 'Error al obtener tareas del alumno'
        ], 500);
    }
}

    public function show(int $taskId): JsonResponse
    {
        try {
            $task = $this->taskRepository->getTaskById($taskId);
            
            if (!$task) {
                return response()->json(['status' => 'error', 'message' => 'Tarea no encontrada'], 404);
            }

            return response()->json(['status' => 'success', 'data' => $task], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching task: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error al obtener tarea'], 500);
        }
    }

    public function submitResolution(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ID_Tarea' => 'required|integer',
                'ID_Alumno' => 'required|integer',
                'Resolucion' => 'required|string'
            ]);

            $resolution = $this->taskRepository->createResolution($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Resolución enviada correctamente',
                'data' => $resolution
            ], 201);
        } catch (Exception $e) {
            \Log::error('Error submitting resolution: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error al enviar resolución'], 500);
        }
    }

    public function getTaskStats(): JsonResponse
    {
        try {
            $stats = $this->taskRepository->getTaskStatistics();
            return response()->json(['status' => 'success', 'data' => $stats], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching stats: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error al obtener estadísticas'], 500);
        }
    }
}