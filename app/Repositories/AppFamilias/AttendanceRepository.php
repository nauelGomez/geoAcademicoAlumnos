<?php

namespace App\Repositories\AppFamilias;

use Illuminate\Support\Facades\DB;

class AttendanceRepository
{
    public function getAttendanceDetail($studentId)
    {
        $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
        if (!$alumno) return [];

        $ciclo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->first();
        $cicloId = $ciclo ? $ciclo->ID : 0;

        // 1. Obtener Mapa de Estados (Presente, Ausente, Tarde, etc.)
        $estados = DB::table('estado')->get()->keyBy('ID');

        // 2. Asistencias Generales (vinculadas a la tabla 'partes')
        $asistenciasGral = DB::table('asistencia as a')
            ->join('partes as p', 'a.ID_Parte', '=', 'p.ID')
            ->where('a.ID_Alumnos', $studentId)
            ->where('a.ID_Ciclo_Lectivo', $cicloId)
            ->where('p.ID_Curso', $alumno->ID_Curso)
            ->select('a.*', 'p.FECHA as fecha_parte')
            ->get();

        // 3. Asistencias Grupales
        $asistenciasGrupales = DB::table('asistencia_grupal as ag')
            ->leftJoin('materias_grupales as mg', 'ag.ID_Materia', '=', 'mg.ID')
            ->where('ag.ID_Alumnos', $studentId)
            ->where('ag.Fecha', '>=', $ciclo->IPT ?? '2000-01-01')
            ->select('ag.*', 'mg.Materia as materia_nombre', 'mg.D as dep')
            ->get();

        $listado = [];
        $totalFaltas = 0;

        // Procesar Generales
        foreach ($asistenciasGral as $as) {
            $estadoInfo = $estados->get($as->ID_Estado);
            if ($as->ID_Estado == 1) continue; // Si es Presente (ID 1 suele serlo), omitimos del detalle

            $listado[] = [
                'fecha' => date('d/m/Y', strtotime($as->fecha_parte)),
                'fecha_raw' => $as->fecha_parte,
                'tipo' => $estadoInfo->Estado ?? 'N/A',
                'incidencia' => $estadoInfo->Incidencia ?? 0,
                'observaciones' => $as->Observaciones . ($as->Constancia ? " - Const: {$as->Constancia}" : ""),
                'ambito' => 'General'
            ];
            $totalFaltas += ($estadoInfo->Incidencia ?? 0);
        }

        // Procesar Grupales
        foreach ($asistenciasGrupales as $ag) {
            $estadoInfo = $estados->get($ag->ID_Estado);
            if ($ag->ID_Estado == 1) continue;

            $obs = $ag->Observaciones;
            // Lógica SAF (Indumentaria, Malestar, Certificado)
            if ($ag->dep == 1 && $ag->SAF > 0) {
                $labelsSAF = [1 => 'Indumentaria', 2 => 'Malestar', 3 => 'Certificado'];
                $obs = "SAF: " . ($labelsSAF[$ag->SAF] ?? "");
            }

            $listado[] = [
                'fecha' => date('d/m/Y', strtotime($ag->Fecha)),
                'fecha_raw' => $ag->Fecha,
                'tipo' => $estadoInfo->Estado ?? 'N/A',
                'incidencia' => $estadoInfo->Incidencia ?? 0,
                'observaciones' => "{$ag->materia_nombre} - {$obs}",
                'ambito' => 'Materia Grupal'
            ];
            $totalFaltas += ($estadoInfo->Incidencia ?? 0);
        }

        // Ordenar por fecha descendente
        usort($listado, function($a, $b) {
            return strcmp($b['fecha_raw'], $a['fecha_raw']);
        });

        return [
            'total_inasistencias' => $totalFaltas,
            'historial' => $listado
        ];
    }
}
