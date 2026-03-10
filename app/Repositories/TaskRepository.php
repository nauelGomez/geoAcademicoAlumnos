<?php

namespace App\Repositories;

use App\Models\Task;
use App\Models\Student;
use App\Models\TaskResolution;

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
        $student = Student::with(['course', 'level'])->find($studentId);
        if (!$student) return null;

        $tasks = Task::with(['subject', 'course', 'teacher', 'resolutions' => function($q) use ($studentId) {
                $q->where('ID_Alumno', $studentId);
            }])
            ->active()
            ->orderBy('ID', 'desc')
            ->get();
            // Acá podrías aplicar el filter y map() que tenías en el controller viejo

        return [
            'student' => [
                'ID' => $student->ID,
                'Nombre' => $student->Nombre . ' ' . $student->Apellido,
                'Curso' => $student->course->Cursos ?? 'N/A',
                'Nivel' => $student->level->Nivel ?? 'N/A'
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