<?php

namespace App\Repositories\AppFamilias;

use Illuminate\Support\Facades\DB;

class GradeRepository
{
    public function getStudentGrades($studentId)
    {
        $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
        if (!$alumno) return [];

        $ciclo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->first();
        $cicloId = $ciclo ? $ciclo->ID : 0;

        $pubCalNP = DB::table('nivel_parametros')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->value('Pub_Cal_NP');

        // 1. Materias Normales
        $materiasNormales = DB::table('materias as m')
            ->leftJoin('personal as p', 'm.ID_Personal', '=', 'p.ID')
            ->where('m.ID_Curso', $alumno->ID_Curso)
            ->select('m.ID', 'm.Materia', 'p.Apellido as Profe_Apellido')
            ->orderBy('m.Materia')
            ->get();

        // 2. Materias Grupales (Comisiones)
        $materiasGrupales = DB::table('materias_grupales as mg')
            ->join('grupos as g', 'mg.ID', '=', 'g.ID_Materia_Grupal')
            ->leftJoin('personal as p', 'mg.ID_Personal', '=', 'p.ID')
            ->where('g.ID_Alumno', $studentId)
            ->where('g.ID_Ciclo_Lectivo', $cicloId)
            ->select('mg.ID', 'mg.Materia', 'p.Apellido as Profe_Apellido')
            ->orderBy('mg.Materia')
            ->get();

        $libreta = [];

        foreach ($materiasNormales as $mat) {
            $notas = $this->getGradesBySubject($studentId, $mat->ID, $cicloId, $pubCalNP, false);
            if (count($notas) > 0) {
                $libreta[] = [
                    'materia' => $mat->Materia,
                    'docente' => $mat->Profe_Apellido,
                    'tipo' => 'Normal',
                    'calificaciones' => $notas
                ];
            }
        }

        foreach ($materiasGrupales as $mat) {
            $notas = $this->getGradesBySubject($studentId, $mat->ID, $cicloId, $pubCalNP, true);
            if (count($notas) > 0) {
                $libreta[] = [
                    'materia' => $mat->Materia,
                    'docente' => $mat->Profe_Apellido,
                    'tipo' => 'Grupal',
                    'calificaciones' => $notas
                ];
            }
        }

        return $libreta;
    }

    private function getGradesBySubject($studentId, $materiaId, $cicloId, $pubCalNP, $esGrupal)
    {
        $tablaOps = $esGrupal ? 'notas_operaciones_grupales' : 'notas_operaciones';
        $tablaNotas = $esGrupal ? 'notas_parciales_grupales' : 'notas_parciales';

        $ops = DB::table($tablaOps)
            ->where('ID_Materia', $materiaId)
            ->where('ID_Ciclo_Lectivo', $cicloId)
            ->where('Publicado', 1)
            ->where('B', 0)
            ->orderBy('Fecha', 'desc')
            ->get();

        $detalles = [];
        foreach ($ops as $op) {
            if ($op->Promediable == 2 && $pubCalNP != 1) continue;

            $notaAlumno = DB::table($tablaNotas)
                ->where('ID_Alumno', $studentId)
                ->where('Operacion', $op->ID)
                ->where('B', 0)
                ->first();

            if ($notaAlumno) {
                $valorNota = $notaAlumno->Calificacion;
                $escala = DB::table('calificaciones_escalas')->where('ID', $op->Escala)->first();
                
                if ($escala && $escala->Tipo == 2) {
                    $valorNota = DB::table('calificaciones_escalas_detalle')
                        ->where('ID', round($notaAlumno->Calificacion))
                        ->value('Estado') ?: $valorNota;
                }

                $detalles[] = [
                    'valor' => $valorNota,
                    'fecha' => date('d/m/Y', strtotime($notaAlumno->FECHA)),
                    'instrumento' => DB::table('calificaciones')->where('ID', $op->ID_Calificacion)->value('Tipo'),
                    'detalle' => $op->Descripcion,
                    'observaciones' => $notaAlumno->Observaciones,
                    'es_promediable' => ($op->Promediable == 1)
                ];
            }
        }
        return $detalles;
    }
}
