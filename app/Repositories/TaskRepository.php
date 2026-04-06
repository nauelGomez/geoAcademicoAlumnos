<?php

namespace App\Repositories;

use App\Models\Task;
use App\Models\Student;
use App\Models\TaskResolution;
use App\Models\TaskSubmission;

class TaskRepository
{
    public function getAllActiveTasks()
    {
        // Eloquent usa conexión 'tenant'
        return Task::with(['subject', 'course', 'teacher', 'resolutions'])
            ->active() // Asegurate de tener este scope en el modelo Task
            ->orderBy('ID', 'desc')
            ->get();
    }
public function getTasksForStudent(int $studentId)
    {
        // 1. Validamos existencia del alumno (Contexto para la respuesta)
        $student = Student::with(['course', 'level'])->find($studentId);
        
        if (!$student) {
            return null;
        }

        // 2. Select principal sobre 'tareas_envios' (TaskSubmission)
        // Cargamos la relación 'task' (tareas_virtuales) con sus propios filtros
        $submissions = TaskSubmission::where('ID_Destinatario', $studentId)
            ->with([
                'task' => function($query) {
                    // Solo traemos la tarea si está activa (Envio=1, Cerrada=0)
                    $query->active()->with(['subject', 'teacher', 'course']);
                }
            ])
            ->get();

        // 3. Mapeamos los resultados para devolver una lista de objetos de tarea limpios
        // Filtramos los envíos cuya tarea asociada no esté activa o sea nula
        $tasks = $submissions->map(function ($submission) {
            if (!$submission->task) {
                return null;
            }
            
            // Adjuntamos datos del envío a la tarea para tener todo en un solo objeto
            $task = $submission->task;
            $task->info_envio = [
                'ID_Envio'  => $submission->ID,
                'Leido'     => $submission->Leido,
                'Resuelto'  => $submission->Resuelto,
                'Corregido' => $submission->Corregido
            ];
            
            return $task;
        })->filter()->values();

        return [
            'student' => [
                'ID'     => $student->ID,
                'Nombre' => trim($student->Nombre . ' ' . $student->Apellido),
                'Curso'  => $student->course->Cursos ?? 'N/A',
                'Nivel'  => $student->level->Nivel ?? 'N/A'
            ],
            'tasks' => $tasks
        ];
    }
    public function getTaskById(int $taskId)
    {
        return Task::with(['subject', 'course', 'teacher', 'cycle', 'resolutions', 'submissions'])->find($taskId);
    }

    public function createResolution(array $data)
    {
        return TaskResolution::create([
            'ID_Tarea' => $data['ID_Tarea'],
            'ID_Alumno' => $data['ID_Alumno'],
            'Resolucion' => $data['Resolucion'],
            'Fecha' => now(),
            'Correccion' => 0
        ]);
    }

    public function getTaskStatistics()
    {
        return [
            'total_tasks' => Task::active()->count(),
            'pending_tasks' => Task::active()->whereDoesntHave('resolutions')->count(),
            'submitted_tasks' => Task::active()->whereHas('resolutions')->count(),
            'corrected_tasks' => Task::active()->whereHas('resolutions', function($q) {
                $q->where('Correcion', 1);
            })->count(),
            'overdue_tasks' => Task::active()->where('Fecha_Vencimiento', '<', now())->count()
        ];
    }
}