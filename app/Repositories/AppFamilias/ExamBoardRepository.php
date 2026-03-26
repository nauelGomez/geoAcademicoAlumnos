<?php

namespace App\Repositories\AppFamilias;

use App\Models\Alumno;
use Illuminate\Support\Facades\DB;

class ExamBoardRepository
{
    /**
     * Obtiene las mesas de examen disponibles o inscriptas para el alumno.
     * @param int $studentId
     * @return array
     */
    public function getExamBoards($studentId)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) {
            return [];
        }

        // WINDSURF: Acá irá la lógica real de cruce de tablas para las mesas de examen legacy.
        // Por ahora devolvemos un array vacío para estabilizar el endpoint.
        $mesas = []; 

        return $mesas;
    }

    public function getStudentExamData($studentId)
    {
        $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
        if (!$alumno) return null;

        // 1. Ciclo y Periodo Vigente
        $ciclo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->first();
        $cicloId = $ciclo ? $ciclo->ID : 0;

        $periodoVigente = DB::table('mesas_examen_periodos')
            ->where('Vigente', 'SI')
            ->where('ID_Ciclo_Lectivo', $cicloId)
            ->first();

        $idPeriodoActual = $periodoVigente ? $periodoVigente->ID : 0;

        // 2. Inscripciones del Periodo Actual
        $currentInscriptions = $this->getInscriptions($studentId, $cicloId, $idPeriodoActual, true);

        // 3. Inscripciones Históricas (Periodos anteriores)
        $historyInscriptions = $this->getInscriptions($studentId, $cicloId, $idPeriodoActual, false);

        return [
            'periodo_actual' => [
                'id' => $idPeriodoActual,
                'nombre' => $periodoVigente->Periodo ?? 'Sin periodo activo',
                'inscripcion_abierta' => $periodoVigente ? (bool)$periodoVigente->Abierta : false
            ],
            'inscripciones_vigentes' => $currentInscriptions,
            'historial' => $historyInscriptions
        ];
    }

    private function getInscriptions($studentId, $cicloId, $periodoId, $isCurrent)
    {
        $query = DB::table('mesas_examen_inscripcion as i')
            ->join('materias as m', 'i.ID_Materia', '=', 'm.ID')
            ->join('cursos as c', 'i.ID_Curso', '=', 'c.ID')
            ->where('i.ID_Alumno', $studentId)
            ->where('i.B', 0);

        if ($isCurrent) {
            $query->where('i.ID_Periodo', $periodoId)->where('i.ID_Ciclo_Lectivo', $cicloId);
        } else {
            $query->where('i.ID_Periodo', '<>', $periodoId);
        }

        return $query->select('i.*', 'm.Materia as materia_nombre', 'c.Cursos as curso_nombre')
            ->orderBy('i.ID', 'desc')
            ->get()
            ->map(function($ins) use ($studentId) {
                return $this->formatInscriptionData($ins, $studentId);
            });
    }

    private function formatInscriptionData($ins, $studentId)
    {
        $statusText = 'Pendiente';
        $detalleExtra = null;

        if ($ins->Estado == 'C') {
            $mesa = DB::table('mesas_examen')->where('ID', $ins->ID_Mesa)->first();
            if ($mesa) {
                if ($mesa->Cierre == 'SI') {
                    $nota = DB::table('notas_mesa_examen')
                        ->where('ID_Mesa', $mesa->ID)
                        ->where('ID_Alumno', $studentId)
                        ->first();
                    
                    $calificacion = ($nota && $nota->Calificacion > 0) ? $nota->Calificacion : 'Ausente';
                    $statusText = 'Finalizada';
                    $detalleExtra = "Nota: $calificacion (Libro: {$nota->Libro} Folio: {$nota->Folio})";
                } else {
                    $profe = DB::table('personal')->where('ID', $mesa->ID_Titular)->value('Apellido');
                    $fechaMesa = date('d/m/Y', strtotime($mesa->Fecha));
                    $statusText = 'Confirmada';
                    $detalleExtra = "$fechaMesa - {$mesa->Hora} hs (Prof. $profe)";
                }
            }
        }

        return [
            'id' => $ins->ID,
            'materia' => $ins->materia_nombre,
            'curso' => $ins->curso_nombre,
            'estado_codigo' => $ins->Estado,
            'estado_label' => $statusText,
            'detalle' => $detalleExtra,
            'fecha_inscripcion' => date('d/m/Y', strtotime($ins->Fecha))
        ];
    }
}
