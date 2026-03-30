<?php

namespace App\Repositories;

use App\Models\Student;
use App\Models\SubjectGroup;
use App\Models\AttendanceGroup;
use App\Models\Cycle;
use App\Models\Grupo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceRepository
{
    /**
     * Resumen general del alumno.
     */
    public function getStudentSummary(int $studentId, array $filters = [])
    {
        $student = Student::with(['course', 'level'])->find($studentId);
        if (!$student) {
            return null;
        }

        $cycle = $this->resolveCycleForStudent($student);
        if (!$cycle) {
            return null;
        }

        $attendances = AttendanceGroup::with('state')
            ->where('ID_Alumnos', $studentId)
            ->where('ID_Ciclo_Lectivo', $cycle->ID)
            ->get();

        $totalClassDays = $attendances
            ->pluck('Fecha')
            ->filter()
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->unique()
            ->count();

        $unjustifiedAbsences = $attendances->filter(function ($attendance) {
            return $this->isUnjustifiedAbsence($attendance);
        })->count();

        $justifiedAbsences = $attendances->filter(function ($attendance) {
            return $this->isJustifiedAbsence($attendance);
        })->count();

        $lates = $attendances->filter(function ($attendance) {
            return $this->isLateState($attendance);
        })->count();

        $earlyDepartures = $attendances->filter(function ($attendance) {
            return $this->isEarlyDepartureState($attendance);
        })->count();

        $others = $attendances->filter(function ($attendance) {
            return $this->isOtherState($attendance);
        })->count();

        $totalAbsences = $unjustifiedAbsences + $justifiedAbsences;

        $attendancePercentage = $totalClassDays > 0
            ? round((($totalClassDays - $totalAbsences) / $totalClassDays) * 100)
            : 0;

        return [
            'student' => [
                'id' => $student->ID,
                'full_name' => trim($student->Nombre . ' ' . $student->Apellido),
                'course' => $student->course ? $student->course->Cursos : null,
                'level' => $student->level ? $student->level->Nivel : null,
            ],
            'cycle' => [
                'id' => $cycle->ID,
                'name' => (string) ($cycle->Ciclo_lectivo ? $cycle->Ciclo_lectivo : $cycle->ID),
                'vigente' => $cycle->Vigente,
            ],
            'summary' => [
                'total_class_days' => $totalClassDays,
                'unjustified_absences' => $unjustifiedAbsences,
                'justified_absences' => $justifiedAbsences,
                'lates' => $lates,
                'early_departures' => $earlyDepartures,
                'others' => $others,
                'attendance_percentage' => $attendancePercentage,
            ],
        ];
    }

    /**
     * Listado de materias con resumen de asistencias por cuatrimestre.
     */
    public function getSubjectsAttendance(int $studentId)
    {
        $student = Student::find($studentId);
        if (!$student) {
            return null;
        }

        $cycle = $this->resolveCycleForStudent($student);
        if (!$cycle) {
            return null;
        }

        $enrolledIds = Grupo::where('ID_Alumno', $studentId)
            ->where('ID_Ciclo_Lectivo', $cycle->ID)
            ->pluck('ID_Materia_Grupal')
            ->filter()
            ->unique()
            ->values();

        if ($enrolledIds->isEmpty()) {
            return [
                'cycle_name' => (string) ($cycle->Ciclo_lectivo ? $cycle->Ciclo_lectivo : $cycle->ID),
                'subjects' => [],
            ];
        }

        $subjects = SubjectGroup::whereIn('ID', $enrolledIds->toArray())
            ->orderBy('Materia', 'asc')
            ->get();

        $allAttendances = AttendanceGroup::with('state')
            ->where('ID_Alumnos', $studentId)
            ->where('ID_Ciclo_Lectivo', $cycle->ID)
            ->whereIn('ID_Materia', $subjects->pluck('ID')->toArray())
            ->orderBy('Fecha', 'asc')
            ->get()
            ->groupBy('ID_Materia');

        $subjectList = $subjects->map(function ($subject) use ($allAttendances, $cycle) {
            $attendances = $allAttendances->get($subject->ID, collect());

            $cuat1 = $attendances->filter(function ($attendance) use ($cycle) {
                return $this->resolveCuatrimestre($attendance, $cycle) === 1;
            });

            $cuat2 = $attendances->filter(function ($attendance) use ($cycle) {
                return $this->resolveCuatrimestre($attendance, $cycle) === 2;
            });

            $inasistenciasTotal = $attendances->filter(function ($attendance) {
                return $this->isAbsence($attendance);
            })->count();

            $totalClases = $attendances
                ->pluck('Fecha')
                ->filter()
                ->map(function ($date) {
                    return Carbon::parse($date)->format('Y-m-d');
                })
                ->unique()
                ->count();

            return [
                'materia' => $subject->Materia,
                'id_materia' => $subject->ID,
                'total_inasistencias' => $inasistenciasTotal,
                'cuat1' => [
                    'inasistencias_inj' => $cuat1->filter(function ($attendance) {
                        return $this->isUnjustifiedAbsence($attendance);
                    })->count(),
                    'inasistencias_jus' => $cuat1->filter(function ($attendance) {
                        return $this->isJustifiedAbsence($attendance);
                    })->count(),
                ],
                'cuat2' => [
                    'inasistencias_inj' => $cuat2->filter(function ($attendance) {
                        return $this->isUnjustifiedAbsence($attendance);
                    })->count(),
                    'inasistencias_jus' => $cuat2->filter(function ($attendance) {
                        return $this->isJustifiedAbsence($attendance);
                    })->count(),
                ],
                'porcentaje_asistencia' => $totalClases > 0
                    ? round((($totalClases - $inasistenciasTotal) / $totalClases) * 100)
                    : 0,
            ];
        })->values();

        return [
            'cycle_name' => (string) ($cycle->Ciclo_lectivo ? $cycle->Ciclo_lectivo : $cycle->ID),
            'subjects' => $subjectList,
        ];
    }

    /**
     * Detalle de asistencias de una materia.
     */
    public function getSubjectDetail(int $studentId, int $subjectId)
    {
        $student = Student::find($studentId);
        if (!$student) {
            return null;
        }

        $cycle = $this->resolveCycleForStudent($student);
        if (!$cycle) {
            return null;
        }

        $attendances = AttendanceGroup::with('state')
            ->where('ID_Materia', $subjectId)
            ->where('ID_Ciclo_Lectivo', $cycle->ID)
            ->where('ID_Alumnos', $studentId)
            ->orderBy('Fecha', 'desc')
            ->get();

        return [
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'cycle_id' => $cycle->ID,
            'attendances' => $attendances,
        ];
    }

    /**
     * Resuelve el ciclo a usar para el alumno.
     */
    private function resolveCycleForStudent($student)
    {
        $cycle = Cycle::where('ID_Nivel', $student->ID_Nivel)
            ->where('Vigente', 'SI')
            ->orderBy('ID', 'desc')
            ->first();

        if (!$cycle) {
            $cycle = Cycle::where('ID_Nivel', $student->ID_Nivel)
                ->orderBy('ID', 'desc')
                ->first();
        }

        return $cycle;
    }

    /**
     * Devuelve 1 o 2.
     *
     * Regla:
     * - Si Trimestre viene en 1 o 2, se usa ese valor.
     * - Si Trimestre viene en 0, se resuelve por fecha.
     *   Corte principal: IST (inicio segundo tramo del año)
     *   Fallback: FPT (fin primer tramo)
     *   Si no hay fechas de ciclo, cae por mes calendario.
     */
    private function resolveCuatrimestre($attendance, $cycle)
    {
        $trimestre = (int) ($attendance->Trimestre ? $attendance->Trimestre : 0);

        if ($trimestre === 1 || $trimestre === 2) {
            return $trimestre;
        }

        if (!$attendance->Fecha) {
            return 1;
        }

        $fecha = Carbon::parse($attendance->Fecha)->startOfDay();

        if (!empty($cycle->IST)) {
            $inicioSegundoTramo = Carbon::parse($cycle->IST)->startOfDay();
            return $fecha->lt($inicioSegundoTramo) ? 1 : 2;
        }

        if (!empty($cycle->FPT)) {
            $finPrimerTramo = Carbon::parse($cycle->FPT)->endOfDay();
            return $fecha->lte($finPrimerTramo) ? 1 : 2;
        }

        return ((int) $fecha->format('n') <= 7) ? 1 : 2;
    }

    private function isAbsence($attendance)
    {
        $state = $this->getStateName($attendance);

        return in_array($state, [
            'Ausente',
            'Justificada',
            'AusenteDT',
            'JustificadaDT',
            'AusenteRI',
            'AusenteDTSC',
            'JustificadaDTSC',
        ], true);
    }

    private function isUnjustifiedAbsence($attendance)
    {
        $state = $this->getStateName($attendance);

        return in_array($state, [
            'Ausente',
            'AusenteDT',
            'AusenteRI',
            'AusenteDTSC',
        ], true);
    }

    private function isJustifiedAbsence($attendance)
    {
        $state = $this->getStateName($attendance);

        return in_array($state, [
            'Justificada',
            'JustificadaDT',
            'JustificadaDTSC',
        ], true);
    }

    private function isLateState($attendance)
    {
        $state = $this->getStateName($attendance);

        return in_array($state, [
            'Tarde',
            'TardeDT',
            'TardeYRetiro',
        ], true);
    }

    private function isEarlyDepartureState($attendance)
    {
        $state = $this->getStateName($attendance);

        return in_array($state, [
            'Retiro',
            'RetiroDT',
            'TardeYRetiro',
        ], true);
    }

    private function isOtherState($attendance)
    {
        $state = $this->getStateName($attendance);

        if ($state === '' || $state === 'Presente') {
            return false;
        }

        if ($this->isAbsence($attendance)) {
            return false;
        }

        if ($this->isLateState($attendance)) {
            return false;
        }

        if ($this->isEarlyDepartureState($attendance)) {
            return false;
        }

        return true;
    }

    private function getStateName($attendance)
    {
        return trim((string) optional($attendance->state)->Estado);
    }
}
