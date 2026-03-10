<?php
// app/Services/AttendanceService.php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceGroup;
use App\Models\AttendanceState;
use App\Models\Cycle;
use App\Models\GroupSubject;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Obtener el ciclo lectivo vigente para un nivel.
     */
    public function getActiveCycle(int $levelId): ?Cycle
    {
        return Cycle::where('ID_Nivel', $levelId)
                    ->where('Vigente', 'SI')
                    ->first();
    }

    /**
     * Calcular porcentaje general de asistencia del alumno.
     */
    public function calculateOverallAttendancePercentage(int $studentId, int $cycleId): float
    {
        // Total de clases posibles: contamos días con registro de asistencia (individual o grupal)
        $totalClasses = $this->countTotalClassDays($studentId, $cycleId);
        
        // Total de ausencias injustificadas (con incidencia completa)
        $unjustifiedAbsences = $this->countUnjustifiedAbsences($studentId, $cycleId);
        
        if ($totalClasses == 0) {
            return 0;
        }
        
        $presentPercentage = (($totalClasses - $unjustifiedAbsences) / $totalClasses) * 100;
        return round($presentPercentage, 2);
    }

    /**
     * Contar días totales con clases (fechas distintas con asistencia cargada).
     */
    public function countTotalClassDays(int $studentId, int $cycleId): int
    {
        // Asistencias individuales
        $individualDates = Attendance::forStudent($studentId)
            ->forCycle($cycleId)
            ->select('Fecha')
            ->distinct()
            ->pluck('Fecha');
        
        // Asistencias grupales
        $groupDates = AttendanceGroup::forStudent($studentId)
            ->forCycle($cycleId)
            ->select('Fecha')
            ->distinct()
            ->pluck('Fecha');
        
        $allDates = $individualDates->merge($groupDates)->unique();
        return $allDates->count();
    }

    /**
     * Contar ausencias injustificadas (sin justificación).
     */
    public function countUnjustifiedAbsences(int $studentId, int $cycleId): int
    {
        $absentStateIds = AttendanceState::absent()->pluck('ID');
        
        $individual = Attendance::forStudent($studentId)
            ->forCycle($cycleId)
            ->whereIn('ID_Estado', $absentStateIds)
            ->count();
        
        $group = AttendanceGroup::forStudent($studentId)
            ->forCycle($cycleId)
            ->whereIn('ID_Estado', $absentStateIds)
            ->count();
        
        return $individual + $group;
    }

    /**
     * Obtener materias grupales en las que el alumno está inscripto.
     */
    public function getStudentGroupSubjects(int $studentId, int $cycleId)
    {
        // Tabla 'grupos' vincula alumno con materia grupal
        $subjects = DB::table('grupos as g')
            ->join('materias_grupales as mg', 'g.ID_Materia_Grupal', '=', 'mg.ID')
            ->where('g.ID_Alumno', $studentId)
            ->where('g.ID_Ciclo_Lectivo', $cycleId)
            ->select('mg.ID', 'mg.Materia', 'mg.ID_Ciclo_Lectivo')
            ->get();
        
        return $subjects;
    }

    /**
     * Total de clases dictadas para una materia grupal (días con asistencia cargada).
     */
    public function countGroupSubjectClasses(int $studentId, int $subjectId, int $cycleId): int
    {
        return AttendanceGroup::forStudent($studentId)
            ->forCycle($cycleId)
            ->where('ID_Materia', $subjectId)
            ->select('Fecha')
            ->distinct()
            ->count('Fecha');
    }

    /**
     * Contar inasistencias (injustificadas/justificadas) para una materia y cuatrimestre.
     */
    public function countAbsencesByType(
        int $studentId,
        int $subjectId,
        int $cycleId,
        string $type, // 'unjustified' o 'justified'
        ?int $quarter = null // 1 o 2, null = todo el ciclo
    ): int {
        $stateIds = $this->getStateIdsByType($type);
        
        $query = AttendanceGroup::forStudent($studentId)
            ->forCycle($cycleId)
            ->where('ID_Materia', $subjectId)
            ->whereIn('ID_Estado', $stateIds);
        
        if ($quarter) {
            $query->whereIn('Fecha', $this->getQuarterDateRange($cycleId, $quarter));
        }
        
        return $query->count();
    }

    /**
     * Obtener IDs de estados según tipo (injustificada/justificada).
     */
    private function getStateIdsByType(string $type): array
    {
        if ($type === 'unjustified') {
            return AttendanceState::absent()->pluck('ID')->toArray();
        } elseif ($type === 'justified') {
            return AttendanceState::justified()->pluck('ID')->toArray();
        }
        return [];
    }

    /**
     * Obtener rango de fechas para un cuatrimestre dentro del ciclo.
     * (Implementación simple: divide el ciclo en dos mitades)
     */
    private function getQuarterDateRange(int $cycleId, int $quarter): array
    {
        $cycle = Cycle::find($cycleId);
        if (!$cycle || !$cycle->Fecha_Inicio || !$cycle->Fecha_Fin) {
            // Si no hay fechas, retornar array vacío (sin filtro)
            return [];
        }
        
        $start = \Carbon\Carbon::parse($cycle->Fecha_Inicio);
        $end = \Carbon\Carbon::parse($cycle->Fecha_Fin);
        $mid = $start->copy()->addDays($start->diffInDays($end) / 2);
        
        if ($quarter == 1) {
            return [$start->toDateString(), $mid->toDateString()];
        } else {
            return [$mid->addDay()->toDateString(), $end->toDateString()];
        }
    }
}