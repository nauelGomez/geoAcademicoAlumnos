<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CourseGradeRepository
{
    public function getGrades(int $studentId)
    {
        return $this->getCourseGradesEvolution($studentId);
    }

    public function getCourseGradesEvolution(int $studentId)
    {
        // 1. Obtener datos básicos del alumno y su plan de estudio
        $alumno = DB::table('alumnos')
            ->join('cursos', 'alumnos.ID_Curso', '=', 'cursos.ID')
            ->join('planes_estudio', 'cursos.ID_Plan', '=', 'planes_estudio.ID')
            ->select('alumnos.Nombre', 'alumnos.Apellido', 'cursos.ID_Plan', 'planes_estudio.Nombre as Carrera')
            ->where('alumnos.ID', $studentId)
            ->first();

        if (!$alumno || !$alumno->ID_Plan) {
            return null;
        }

        // 2. Traer los años/cursos del plan de estudio
        $cursosPlan = DB::table('planes_estudio_cursos')
            ->where('ID_Plan', $alumno->ID_Plan)
            ->orderBy('Orden')
            ->get();

        // 3. LA CONSULTA MAESTRA: Trae todas las materias y sus notas (si las tiene) de una sola vez
        $materiasConNotas = DB::table('materias_planes as mp')
            ->leftJoin('materias as m', 'm.ID_Materia_Plan', '=', 'mp.ID')
            ->leftJoin('notas_cursada as nc', function($join) use ($studentId) {
                $join->on('nc.ID_Materia', '=', 'm.ID')
                     ->where('nc.ID_Alumno', '=', $studentId);
            })
            ->where('mp.ID_Plan', $alumno->ID_Plan)
            ->select(
                'mp.Curso as ID_Curso_P',
                'mp.Materia as Nombre_Materia',
                'nc.Cursada',
                'nc.Promocion',
                'nc.Calificacion_Cursada',
                'nc.Fecha_Cursada',
                'nc.Calificacion',
                'nc.Fecha',
                'nc.Final',
                'nc.Causa',
                'nc.Observaciones',
                'nc.ID as Tiene_Nota'
            )
            ->orderBy('mp.Orden')
            ->get();

        // 4. Formatear y agrupar los datos para el Frontend
        $evolucion = [];

        foreach ($cursosPlan as $cursoP) {
            $materiasDelCurso = $materiasConNotas->where('ID_Curso_P', $cursoP->ID);
            $materiasFormateadas = [];

            foreach ($materiasDelCurso as $mat) {
                $materiasFormateadas[] = [
                    'materia'       => $mat->Nombre_Materia,
                    'cursada'       => $this->formatCourseGrade($mat),
                    'final'         => $this->formatFinalGrade($mat),
                    'observaciones' => $mat->Observaciones ?? ''
                ];
            }

            $evolucion[] = [
                'anio_curso' => $cursoP->Curso,
                'materias'   => $materiasFormateadas
            ];
        }

        return [
            'perfil' => [
                'alumno'  => $alumno->Nombre . ' ' . $alumno->Apellido,
                'carrera' => $alumno->Carrera
            ],
            'evolucion' => $evolucion
        ];
    }

    private function formatCourseGrade($mat): string
    {
        if (!$mat->Tiene_Nota) {
            return 'Pendiente';
        }

        $fecha = $mat->Fecha_Cursada ? Carbon::parse($mat->Fecha_Cursada)->format('d/m/Y') : 'S/F';
        $cal = $mat->Calificacion_Cursada;

        if ($mat->Cursada == 1) {
            if ($mat->Promocion == 1) {
                return "{$cal} Promocionada ({$fecha})";
            } else {
                return "{$cal} Aprobada ({$fecha})";
            }
        } else {
            return "{$cal} Desaprobada ({$fecha})";
        }
    }

    private function formatFinalGrade($mat): string
    {
        if (!$mat->Tiene_Nota || $mat->Cursada != 1) {
            return ''; 
        }

        if ($mat->Final == 'SI') {
            $fecha = $mat->Fecha ? Carbon::parse($mat->Fecha)->format('d/m/Y') : 'S/F';
            return "{$mat->Calificacion} ({$fecha})";
        }

        return 'Pendiente';
    }
}