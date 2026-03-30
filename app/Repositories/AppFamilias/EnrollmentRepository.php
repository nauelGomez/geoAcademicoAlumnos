<?php

namespace App\Repositories\AppFamilias;

use Illuminate\Support\Facades\DB;

class EnrollmentRepository
{
    public function getAvailableGroups($studentId)
    {
        $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
        if (!$alumno) return [];

        $ciclo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->first();
        $cicloId = $ciclo ? $ciclo->ID : 0;

        // 1. Obtener materias grupales habilitadas para autoinscripción (AI = 'SI')
        $materiasGrupales = DB::table('materias_grupales as mg')
            ->leftJoin('personal as p', 'mg.ID_Personal', '=', 'p.ID')
            ->where('mg.ID_Ciclo_Lectivo', $cicloId)
            ->where('mg.ID_Nivel', $alumno->ID_Nivel)
            ->where('mg.AI', 'SI')
            ->orderBy('mg.DiaCT1')
            ->orderBy('mg.HoraCT1')
            ->select('mg.*', 'p.Apellido as Docente_Apellido')
            ->get();

        $lista = [];

        foreach ($materiasGrupales as $mg) {
            // 2. Contar inscriptos actuales en el grupo
            $inscriptos = DB::table('grupos')
                ->where('ID_Materia_Grupal', $mg->ID)
                ->where('ID_Ciclo_Lectivo', $cicloId) // Aseguramos contar solo los del ciclo actual
                ->count();

            // 3. Verificar si EL ALUMNO ya está inscripto en este grupo
            $yaInscripto = DB::table('grupos')
                ->where('ID_Materia_Grupal', $mg->ID)
                ->where('ID_Alumno', $studentId)
                ->exists();

            $cupoMaximo = (int)$mg->Cupo;
            $disponibles = $cupoMaximo - $inscriptos;
            
            $disponibilidadText = 'Grupo Completo';
            $puedeInscribirse = false;

            if ($disponibles > 0) {
                $disponibilidadText = ($disponibles == 1) ? '1 lugar' : "$disponibles lugares";
                $puedeInscribirse = true;
            }

            if ($yaInscripto) {
                $disponibilidadText = 'Ya estás inscripto';
                $puedeInscribirse = false;
            }

            // Formateo de Horario (Ej: Lunes a las 14:00)
            $horaStr = $mg->HoraCT1 ? date('H:i', strtotime($mg->HoraCT1)) : '';
            $horario = "{$mg->DiaCT1} a las {$horaStr} hs";

            $lista[] = [
                'id_materia' => $mg->ID,
                'materia' => $mg->Materia,
                'docente' => $mg->Docente_Apellido ? "Prof. {$mg->Docente_Apellido}" : 'Sin Asignar',
                'horario' => $horario,
                'cupo_maximo' => $cupoMaximo,
                'inscriptos' => $inscriptos,
                'lugares_disponibles' => max(0, $disponibles),
                'disponibilidad_texto' => $disponibilidadText,
                'permite_inscripcion' => $puedeInscribirse,
                'ya_inscripto' => $yaInscripto
            ];
        }

        return $lista;
    }

    public function enrollStudent($studentId, $materiaId)
    {
        // 1. Validaciones
        $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
        if (!$alumno) throw new \Exception('Alumno no encontrado');

        $ciclo = DB::table('ciclo_lectivo')->where('ID_Nivel', $alumno->ID_Nivel)->where('Vigente', 'SI')->first();
        $cicloId = $ciclo ? $ciclo->ID : 0;

        $mg = DB::table('materias_grupales')->where('ID', $materiaId)->first();
        if (!$mg) throw new \Exception('Materia grupal no encontrada');
        if ($mg->AI != 'SI') throw new \Exception('Esta materia no admite autoinscripción');

        $yaInscripto = DB::table('grupos')
            ->where('ID_Materia_Grupal', $materiaId)
            ->where('ID_Alumno', $studentId)
            ->exists();
        if ($yaInscripto) throw new \Exception('El alumno ya está inscripto en este grupo');

        $inscriptos = DB::table('grupos')->where('ID_Materia_Grupal', $materiaId)->count();
        if ($inscriptos >= $mg->Cupo) throw new \Exception('El grupo ha alcanzado el cupo máximo');

        // 2. Inserción
        return DB::table('grupos')->insert([
            'ID_Materia_Grupal' => $materiaId,
            'ID_Alumno' => $studentId,
            'ID_Ciclo_Lectivo' => $cicloId
        ]);
    }
}
