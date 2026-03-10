<?php

namespace App\Repositories;

use App\Models\Student;
use App\Models\AttendanceGroup;
use App\Models\Cycle;

class AttendanceRepository
{
    public function getStudentSummary(int $studentId, array $filters)
    {
        $student = Student::with(['course', 'level'])->find($studentId);
        if (!$student) return null;

        // Usamos el scope active() que definimos en el modelo
        $cycle = Cycle::byLevel($student->ID_Nivel)->active()->first();
        
        // Si no hay uno marcado con 'SI', traemos el último por ID para que no rompa
        if (!$cycle) {
            $cycle = Cycle::where('ID_Nivel', $student->ID_Nivel)->orderBy('ID', 'desc')->first();
        }

        if (!$cycle) return null;

        return [
            'student' => [
                'id' => $student->ID,
                'full_name' => $student->Nombre . ' ' . $student->Apellido,
                'course' => $student->course->Cursos ?? null,
                'level' => $student->level->Nivel ?? null,
            ],
            'cycle' => [
                'id' => $cycle->ID,
                'name' => $cycle->Ciclo_lectivo ?? $cycle->ID,
                'vigente' => $cycle->Vigente,
            ],
            'summary' => [
                'total_class_days' => 0, 
                'unjustified_absences' => $this->countAbsencesByState($studentId, $cycle->ID, 'unjustified'),
                'justified_absences' => $this->countAbsencesByState($studentId, $cycle->ID, 'justified'),
                'lates' => $this->countAbsencesByState($studentId, $cycle->ID, 'late'),
                'early_departures' => $this->countAbsencesByState($studentId, $cycle->ID, 'early'),
                'others' => $this->countAbsencesByState($studentId, $cycle->ID, 'other'),
                'attendance_percentage' => 100, 
            ],
        ];
    }

    public function getSubjectsAttendance(int $studentId)
    {
        $student = Student::find($studentId);
        if (!$student) return null;

        $cycle = Cycle::byLevel($student->ID_Nivel)->active()->first();
        if (!$cycle) {
            $cycle = Cycle::where('ID_Nivel', $student->ID_Nivel)->orderBy('ID', 'desc')->first();
        }
        
        if (!$cycle) return null;

        return [
            'cycle_id' => $cycle->ID,
            'subjects' => [] 
        ];
    }

   public function getSubjectDetail(int $studentId, int $subjectId)
{
    $student = Student::find($studentId);
    if (!$student) return null;

    $cycle = Cycle::byLevel($student->ID_Nivel)->active()->first();
    if (!$cycle) {
        $cycle = Cycle::where('ID_Nivel', $student->ID_Nivel)->orderBy('ID', 'desc')->first();
    }

    if (!$cycle) return null;

    // Corregimos ID_Alumno -> ID_Alumnos según HeidiSQL
    $attendances = AttendanceGroup::with('state')
        ->where('ID_Materia', $subjectId)
        ->where('ID_Ciclo_Lectivo', $cycle->ID) // Ojo: ver si es ID_Ciclo o ID_Ciclo_Lectivo en Heidi
        ->where('ID_Alumnos', $studentId) // <--- ¡LA PLURALIZACIÓN ERA EL SECRETO!
        ->orderBy('Fecha', 'desc')
        ->get();

    return [
        'student_id' => $studentId,
        'subject_id' => $subjectId,
        'cycle_id' => $cycle->ID,
        'attendances' => $attendances
    ];
}

    private function countAbsencesByState(int $studentId, int $cycleId, string $type): int
    {
        // Lógica de conteo según tus estados
        return 0; 
    }
}