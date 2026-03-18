<?php

namespace App\Repositories\AppFamilias;

use App\Models\Alumno;
use Illuminate\Support\Facades\DB;

class FamilyWallRepository
{
    public function getWalls($studentId)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) return [];

        // 1. Obtener Ciclo Lectivo Vigente
        $ciclo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->first();
        
        $cicloId = $ciclo ? $ciclo->ID : 0;
        $ficl = $ciclo ? $ciclo->IPT : '2000-01-01';

        // 2. Muros del Curso (Normales)
        $murosCurso = DB::table('tareas_materia_muro as m')
            ->leftJoin('materias as mat', 'm.ID_Materia', '=', 'mat.ID')
            ->leftJoin('personal as p', 'm.ID_Usuario', '=', 'p.ID')
            ->where('m.B', 0)
            ->where('m.ID_Curso', $alumno->ID_Curso)
            ->where('m.Fecha', '>=', $ficl)
            ->select('m.*', 'mat.Materia as Materia_Nombre', 'p.Apellido', 'p.Nombre')
            ->get();

        // 3. Muros de Materias Grupales (Donde el alumno está inscripto)
        $murosGrupales = DB::table('tareas_materia_muro as m')
            ->join('materias_grupales as mg', 'm.ID_Materia', '=', 'mg.ID')
            ->join('grupos as g', 'mg.ID', '=', 'g.ID_Materia_Grupal')
            ->leftJoin('personal as p', 'm.ID_Usuario', '=', 'p.ID')
            ->where('m.B', 0)
            ->where('m.ID_Curso', 0)
            ->where('m.Tipo_Materia', 'g')
            ->where('m.Fecha', '>=', $ficl)
            ->where('g.ID_Alumno', $studentId)
            ->where('g.ID_Ciclo_Lectivo', $cicloId)
            ->select('m.*', 'mg.Materia as Materia_Nombre', 'p.Apellido', 'p.Nombre')
            ->get();

        // Combinar ambas listas
        $todosLosMuros = $murosCurso->merge($murosGrupales)->sortByDesc('ID');

        $resultado = [];
        foreach ($todosLosMuros as $muro) {
            // Contar novedades sin leer en este muro específico
            $sinLeer = $this->countUnreadWallMessages($muro->ID, $studentId);

            $resultado[] = [
                'id' => $muro->ID,
                'fecha' => date('d/m/Y', strtotime($muro->Fecha)),
                'materia' => $muro->Materia_Nombre ?? 'General',
                'titulo' => $muro->Titulo,
                'docente' => "{$muro->Apellido}, {$muro->Nombre}",
                'sin_leer' => $sinLeer > 0,
                'cantidad_sin_leer' => $sinLeer
            ];
        }

        return $resultado;
    }

    private function countUnreadWallMessages($muroId, $studentId)
    {
        // Busca en el detalle del muro y cruza con la tabla de lecturas del alumno
        return DB::table('tareas_materia_muro_detalle as d')
            ->leftJoin('tareas_materia_muro_lecturas as l', function($join) use ($studentId) {
                $join->on('d.ID', '=', 'l.ID_Muro_Detalle')
                     ->where('l.ID_Alumno', '=', (int)$studentId);
            })
            ->where('d.ID_Muro', $muroId)
            ->where('d.Tipo_Usuario', 'D') // Solo mensajes de docentes
            ->where('d.B', 0)
            ->whereNull('l.ID') // Que no tengan registro de lectura
            ->count();
    }
}
