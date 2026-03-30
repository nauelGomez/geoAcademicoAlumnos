<?php

namespace App\Repositories\AppFamilias;

use App\Models\Alumno;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FamilyDashboardRepository
{
    public function getDashboardData($studentId)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) return null;

        $hoy = date("Y-m-d");

        // 1. Ciclo Lectivo y Nivel
        $ciclo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->first();
        
        $cicloId = $ciclo ? $ciclo->ID : 0;
        $ficl = $ciclo ? $ciclo->IPT : '2000-01-01'; // Fecha inicio ciclo

        // 2. Lógica de Burbujas (Agrupaciones)
        $agrupacion = DB::table('agrupaciones as a')
            ->join('agrupaciones_detalle as ad', 'a.ID', '=', 'ad.ID_Grupo')
            ->where('a.ID_Curso', $alumno->ID_Curso)
            ->where('a.ID_Ciclo_Lectivo', $cicloId)
            ->where('ad.ID_Alumno', $studentId)
            ->select('a.ID', 'a.Grupo')
            ->first();

        $idAgrupacion = $agrupacion ? $agrupacion->ID : null;

        // 3. Contadores de Novedades (Burbujas de colores)
        $stats = [
            'clases_sin_leer'   => $this->countUnreadClasses($studentId, $idAgrupacion),
            'tareas_sin_leer'   => DB::table('tareas_envios')->where('ID_Destinatario', $studentId)->where('Leido', 0)->count(),
            'recursos_sin_leer' => $this->countUnreadResources($studentId, $alumno->ID_Curso, $ficl, $idAgrupacion, $cicloId),
            'muros_sin_leer'    => $this->countUnreadWalls($studentId, $alumno->ID_Curso, $ficl, $cicloId),
            'inasistencias'     => $this->getInasistenciasSafety($studentId, $cicloId)
        ];

        // 4. Proceso de Valoración (El promedio general del alumno)
        $valoracion = $this->getPedagogicalEvaluation($studentId, $alumno->ID_Nivel, $cicloId);

        // 5. Agenda (Eventos)
        $agenda = $this->getUpcomingEvents($studentId, $alumno->ID_Curso, $idAgrupacion, $cicloId);

        // 6. Listado de Materias con % de Tareas Resueltas
        $materias = $this->getSubjectsProgress($studentId, $alumno->ID_Curso, $cicloId);

        return [
            'perfil' => [
                'id' => $alumno->ID,
                'nombre' => "{$alumno->Apellido}, {$alumno->Nombre}",
                'curso' => DB::table('cursos')->where('ID', $alumno->ID_Curso)->value('Cursos'),
                'nivel' => DB::table('nivel')->where('ID', $alumno->ID_Nivel)->value('Nivel'),
                'burbuja' => $agrupacion ? $agrupacion->Grupo : null,
            ],
            'novedades' => $stats,
            'valoracion_general' => $valoracion,
            'agenda' => $agenda,
            'materias' => $materias
        ];
    }

    private function getInasistenciasSafety($studentId, $cicloId)
    {
        if (!\Schema::hasTable('inasistencias')) return 0;
        return DB::table('inasistencias')
            ->where('ID_Alumno', $studentId)
            ->where('ID_Ciclo', $cicloId)
            ->sum('Valor') ?: 0;
    }

    private function countUnreadClasses($studentId, $idAgrupacion)
    {
        $hoy = date('Y-m-d');
        // Filtra clases enviadas, no leídas, activas y que ya se publicaron
        return DB::table('clases_virtuales_envios as e')
            ->join('clases_virtuales as c', 'e.ID_Clase', '=', 'c.ID')
            ->where('e.ID_Destinatario', $studentId)
            ->where('e.Leido', 0)
            ->where('c.Estado', 1)
            ->where('c.Fecha_Publicacion', '<=', $hoy)
            ->count();
    }

    private function countUnreadResources($studentId, $courseId, $ficl, $idAgrupacion, $cicloId)
    {
        return DB::table('clases_virtuales_contenidos as c')
            ->leftJoin('clases_virtuales_contenidos_lecturas as l', function($join) use ($studentId) {
                $join->on('c.ID', '=', 'l.ID_Contenido')->where('l.ID_Alumno', '=', (int)$studentId);
            })
            ->where('c.Estado', 1)
            ->where('c.Fecha', '>=', $ficl)
            ->whereNull('l.ID')
            ->where(function($q) use ($courseId, $studentId, $cicloId) {
                $q->where('c.ID_Curso', $courseId)
                  ->orWhereIn('c.ID_Materia', function($sub) use ($studentId, $cicloId) {
                      $sub->select('ID_Materia_Grupal')->from('grupos')
                          ->where('ID_Alumno', $studentId)->where('ID_Ciclo_Lectivo', $cicloId);
                  });
            })
            ->count();
    }

    private function countUnreadWalls($studentId, $courseId, $ficl, $cicloId)
    {
        // Lógica de muros sin leer del código legacy
        return DB::table('tareas_materia_muro_detalle as d')
            ->join('tareas_materia_muro as m', 'd.ID_Muro', '=', 'm.ID')
            ->leftJoin('tareas_materia_muro_lecturas as l', function($join) use ($studentId) {
                $join->on('d.ID', '=', 'l.ID_Muro_Detalle')->where('l.ID_Alumno', '=', (int)$studentId);
            })
            ->where('m.Fecha', '>=', $ficl)
            ->where('m.B', 0)
            ->whereNull('l.ID')
            ->where(function($q) use ($courseId, $studentId, $cicloId) {
                $q->where('d.ID_Curso', $courseId)
                  ->orWhereIn('m.ID_Materia', function($sub) use ($studentId, $cicloId) {
                      $sub->select('ID_Materia_Grupal')->from('grupos') // Nota: ajusté a tu tabla grupos
                          ->where('ID_Alumno', $studentId)->where('ID_Ciclo_Lectivo', $cicloId);
                  });
            })
            ->count();
    }

    private function getPedagogicalEvaluation($studentId, $levelId, $cicloId)
    {
        $proceso = DB::table('procesos_valoracion')
            ->where('ID_Nivel', $levelId)
            ->where('ID_Ciclo_Lectivo', $cicloId)
            ->where('Envio', 1)
            ->orderBy('ID', 'desc')
            ->first();

        if (!$proceso) return "Sin Datos";

        $alumnoVal = DB::table('procesos_valoracion_alumno')
            ->where('ID_Alumno', $studentId)
            ->where('ID_Proyecto', $proceso->ID)
            ->first();

        if (!$alumnoVal || empty($alumnoVal->Calificacion)) return "Sin Datos";

        $c = $alumnoVal->Calificacion;
        
        // Mapeo N1 a N5 según escala del colegio
        if ($c == 1 || $c <= $proceso->L1) return $proceso->N1;
        if ($c <= $proceso->L2) return $proceso->N2;
        if ($c <= $proceso->L3) return $proceso->N3;
        if ($c <= $proceso->L4) return $proceso->N4;
        return $proceso->N5;
    }

    private function getUpcomingEvents($studentId, $courseId, $idAgrupacion, $cicloId)
    {
        return DB::table('agenda_comun')
            ->where('Fecha_R', '>=', date('Y-m-d'))
            ->where('B', 0)
            ->where(function($q) use ($courseId) {
                $q->where('ID_Curso', $courseId)->orWhere('ID_Curso', 0);
            })
            ->orderBy('Fecha_R', 'asc')
            ->orderBy('Hora_Inicio', 'asc')
            ->limit(5)
            ->get();
    }

    private function getSubjectsProgress($studentId, $courseId, $cicloId)
    {
        $materias = DB::table('materias')
            ->where('ID_Curso', $courseId)
            ->orderBy('Materia')
            ->get();

        return $materias->map(function($mat) use ($studentId, $cicloId) {
            $total = DB::table('tareas_virtuales')
                ->where('ID_Materia', $mat->ID)
                ->where('ID_Ciclo_Lectivo', $cicloId)
                ->count();

            $resueltas = 0;
            if ($total > 0) {
                $resueltas = DB::table('tareas_resoluciones')
                    ->where('ID_Alumno', $studentId)
                    ->whereIn('ID_Tarea', function($q) use ($mat, $cicloId) {
                        $q->select('ID')->from('tareas_virtuales')
                          ->where('ID_Materia', $mat->ID)
                          ->where('ID_Ciclo_Lectivo', $cicloId);
                    })->count();
            }

            return [
                'id' => $mat->ID,
                'nombre' => $mat->Materia,
                'nombre_corto' => $mat->Nombre_Corto ?: $mat->Materia,
                'porcentaje' => $total > 0 ? round(($resueltas / $total) * 100) : 0
            ];
        });
    }
}
