<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class InscripcionRepository
{
    public function getMateriasDisponibles($alumnoId)
    {
        // 1. Obtener datos básicos del alumno
        $alumno = DB::table('alumnos')->where('ID', $alumnoId)->first();
        if (!$alumno) return [];

        // 2. Obtener Ciclo Lectivo y Parámetros de Nivel
        $ciclo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->first();

        $parametros = DB::table('nivel_parametros')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->first();

        // Si el periodo no está abierto (HabAI), retornamos vacío o un error
        if (!$parametros || $parametros->HabAI != 1) {
            return ['abierto' => false, 'data' => []];
        }

        // 3. Obtener el plan y orden actual
        $cursoActual = DB::table('cursos')->where('ID', $alumno->ID_Curso)->first();
        $ordenPlan = $cursoActual->Orden_Plan ?? 0;

        // 4. Query principal de materias grupales habilitadas
        // Filtramos por materias que pertenecen al plan y curso del alumno (o inferiores)
        $materiasGrupales = DB::table('materias_grupales as mg')
            ->join('materias as m', 'mg.ID_Materia', '=', 'm.ID')
            ->join('materias_planes as mp', 'm.ID_Materia_Plan', '=', 'mp.ID')
            ->join('planes_estudio_cursos as pec', 'mp.Curso', '=', 'pec.ID')
            ->leftJoin('personal as p', 'mg.ID_Personal', '=', 'p.ID')
            ->where('mg.ID_Ciclo_Lectivo', $ciclo->ID)
            ->where('mg.AI', 'SI')
            ->where('pec.ID_Plan', $cursoActual->ID_Plan)
            ->where('pec.Orden', '<=', $ordenPlan)
            ->select(
                'mg.*', 
                'm.ID as materia_real_id', 
                'p.Apellido as docente_apellido',
                'mg.Materia as nombre_materia'
            )
            ->get();

        $resultado = [];

        foreach ($materiasGrupales as $mg) {
            // A. Verificar si ya aprobó el Final
            $yaAprobada = DB::table('notas_cursada')
                ->where('ID_Alumno', $alumnoId)
                ->where('ID_Materia', $mg->materia_real_id)
                ->where('Final', 'SI')
                ->exists();

            if ($yaAprobada) continue;

            // B. Verificar si ya está inscripto
            $yaInscripto = DB::table('grupos')
                ->where('ID_Alumno', $alumnoId)
                ->where('ID_Materia_Grupal', $mg->ID)
                ->exists();

            if ($yaInscripto) continue;

            // C. Verificar Correlativas
            $correlativas = DB::table('planes_estudio_correlativas')
                ->where('ID_Materia', $mg->materia_real_id)
                ->get();

            $autorizado = true;
            $causal = "";

            foreach ($correlativas as $corr) {
                $aprobada = DB::table('notas_cursada')
                    ->where('ID_Alumno', $alumnoId)
                    ->where('ID_Materia', $corr->ID_Materia_C)
                    ->where('Final', 'SI')
                    ->exists();

                if (!$aprobada) {
                    $materiaFaltante = DB::table('materias')->where('ID', $corr->ID_Materia_C)->value('Materia');
                    $autorizado = false;
                    $causal = "Bloqueado por falta de final en: " . $materiaFaltante;
                    break; 
                }
            }

            // D. Calcular Cupos
            $inscriptos = DB::table('grupos')->where('ID_Materia_Grupal', $mg->ID)->count();
            $disponibilidad = "Grupo Completo";
            $puedeInscribirse = false;

            if ($mg->Cupo == 0) {
                $disponibilidad = "Lugares Disponibles";
                $puedeInscribirse = true;
            } elseif ($inscriptos < $mg->Cupo) {
                $restante = $mg->Cupo - $inscriptos;
                $disponibilidad = $restante . ($restante == 1 ? " lugar" : " lugares");
                $puedeInscribirse = true;
            }

            $resultado[] = [
                'id' => $mg->ID,
                'materia' => $mg->nombre_materia,
                'docente' => "Prof. " . $mg->docente_apellido,
                'horario' => $mg->DiaCT1 . " a las " . substr($mg->HoraCT1, 0, 5),
                'disponibilidad' => $disponibilidad,
                'autorizado' => $autorizado,
                'causal' => $causal,
                'puede_click' => ($autorizado && $puedeInscribirse)
            ];
        }

        return ['abierto' => true, 'data' => $resultado];
    }
    public function obtenerOfertaAcademica(int $alumnoId)
{
    // Intentar usar el modelo Alumno si existe; devolver false si no existe para mantener compatibilidad con el controlador.
    $alumnoClass = \App\Models\Alumno::class;
    if (!class_exists($alumnoClass)) {
        return false;
    }

    $alumno = $alumnoClass::find($alumnoId);
    if (!$alumno) {
        return false;
    }

    // Si el modelo define la relación ofertaAcademica, devolverla; si no, devolver colección vacía.
    if (method_exists($alumno, 'ofertaAcademica')) {
        return $alumno->ofertaAcademica()->get();
    }

    return collect();
}
}