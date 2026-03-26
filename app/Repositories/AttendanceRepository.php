<?php

namespace App\Repositories;

use App\Models\Student;
use App\Models\SubjectGroup;
use App\Models\AttendanceGroup;
use App\Models\Cycle;
use App\Models\Grupo;
use Illuminate\Support\Facades\DB;

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

    /**
     * Obtiene el listado de materias y estadísticas de asistencia para un alumno.
     */
    public function getSubjectsAttendance(int $studentId)
    {
        $student = Student::find($studentId);
        if (!$student) return null;

        // Buscar ciclo vigente por nivel
        $cycle = Cycle::where('ID_Nivel', $student->ID_Nivel)
                      ->where('Vigente', 'SI')
                      ->first();

        if (!$cycle) return null;

        // 1. Obtener materias inscritas (campo: ID_Alumno)
        $enrolledIds = Grupo::where('ID_Alumno', $studentId)
                            ->where('ID_Ciclo_Lectivo', $cycle->ID)
                            ->pluck('ID_Materia_Grupal');

        $subjects = SubjectGroup::whereIn('ID', $enrolledIds)
                                ->orderBy('Materia', 'asc')
                                ->get();

        $subjectList = $subjects->map(function ($subject) use ($studentId, $cycle) {
            // 2. Obtener asistencias (campo: ID_Alumnos)
            $attendances = AttendanceGroup::with('state')
                ->where('ID_Alumnos', $studentId)
                ->where('ID_Materia', $subject->ID)
                ->where('ID_Ciclo_Lectivo', $cycle->ID)
                ->get();

            $cuat1 = $attendances->where('Cuatrimestre', 1);
            $cuat2 = $attendances->where('Cuatrimestre', 2);

            // Totales e inasistencias (se consideran inasistencias los estados de ausencia)
            $inasistenciasTotal = $attendances->whereIn('state.Estado', ['Ausente', 'AusenteDT', 'AusenteRI'])->count();
            $totalClases = $attendances->unique('Fecha')->count();

            return [
                'materia' => $subject->Materia,
                'id_materia' => $subject->ID,
                'total_inasistencias' => $inasistenciasTotal,
                'cuat1' => [
                    'inasistencias_inj' => $cuat1->where('state.Estado', 'AusenteDT')->count(),
                    'inasistencias_jus' => $cuat1->where('state.Estado', 'JustificadaDT')->count(),
                ],
                'cuat2' => [
                    'inasistencias_inj' => $cuat2->where('state.Estado', 'AusenteDT')->count(),
                    'inasistencias_jus' => $cuat2->where('state.Estado', 'JustificadaDT')->count(),
                ],
                'porcentaje_asistencia' => $totalClases > 0 
                    ? round((($totalClases - $inasistenciasTotal) / $totalClases) * 100) 
                    : 0
            ];
        });

        return [
            'cycle_name' => (string) ($cycle->Ciclo_lectivo ?? $cycle->ID),
            'subjects' => $subjectList
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

    private function getTotalClassesCount($subjectId, $cycleId)
    {
        // Esta query cuenta cuántos días se tomó asistencia para esa materia en ese ciclo
        return DB::table('asistencia_grupal')
            ->where('ID_Materia', $subjectId)
            ->where('ID_Ciclo_Lectivo', $cycleId)
            ->distinct('Fecha')
            ->count('Fecha');
    }

    private function countAbsencesByState(int $studentId, int $cycleId, string $type): int
    {
        // Lógica de conteo según tus estados
        return 0; 
    }
}