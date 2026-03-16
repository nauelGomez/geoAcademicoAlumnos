<?php

namespace App\Repositories;

use App\Models\Alumno;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardRepository
{
    public function getDashboardMetrics(int $alumnoId, $emailResponsable = null): array
    {
        // 1. Obtener info base del alumno con sus relaciones
        $alumno = Alumno::with(['curso', 'nivel'])->findOrFail($alumnoId);
        
        $cicloLectivo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->first();

        // Extraemos variables seguras
        $ficl = $cicloLectivo->FICL ?? null;
        $cicloId = $cicloLectivo->ID ?? null;

        // 2. Obtener métricas consolidadas
        return [
            'perfil' => [
                'nombre_completo' => $alumno->Nombre . ' ' . $alumno->Apellido,
                'curso'           => $alumno->curso->Cursos ?? 'Sin curso',
                'nivel'           => $alumno->nivel->Nivel ?? 'Sin nivel',
            ],
            'metricas' => [
                'clases_nuevas'     => $this->getUnreadClasses($alumnoId, $emailResponsable, $ficl),
                'tareas_nuevas'     => $this->getUnreadTasks($alumnoId, $emailResponsable),
                'recursos_nuevos'   => $this->getUnreadResources($alumnoId, $alumno->ID_Curso, $ficl),
                'novedades_muros'   => $this->getUnreadWallNews($alumnoId, $alumno->ID_Curso, $ficl),
                'novedades_tareas'  => $this->getTaskNews($alumnoId, $cicloId),
                'asistencia_faltas' => $this->getInasistencias($alumnoId),
            ],
            'co2' => $this->getLatestCo2Measurement($alumno->ID_Curso),
            'eventos_proximos' => $this->getUpcomingEvents($alumno)
        ];
    }

    private function getUnreadTasks(int $alumnoId, $email = null): int
    {
        return DB::table('tareas_envios')
            ->where('ID_Destinatario', $alumnoId)
            ->when($email, function($query) use ($email) {
                return $query->where('MailD', $email);
            })
            ->where('Leido', 0)
            ->count();
    }

    private function getUnreadClasses(int $alumnoId, $email = null, $ficl = null): int
    {
        $hoy = Carbon::today()->toDateString();
        
        return DB::table('clases_virtuales_envios as cve')
            ->join('clases_virtuales as cv', 'cve.ID_Clase', '=', 'cv.ID')
            ->where('cve.ID_Destinatario', $alumnoId)
            ->when($email, function($query) use ($email) {
                return $query->where('cve.MailD', $email);
            })
            ->where('cve.Leido', 0)
            ->where('cv.Estado', 1)
            ->where('cv.Fecha_Publicacion', '<=', $hoy)
            ->count();
    }

    private function getUnreadResources(int $alumnoId, $cursoId = null, $ficl = null): int
    {
        $hoy = Carbon::today()->toDateString();

        return DB::table('clases_virtuales_contenidos as cvc')
            ->leftJoin('clases_virtuales_contenidos_lecturas as cvcl', function($join) use ($alumnoId) {
                $join->on('cvc.ID', '=', 'cvcl.ID_Contenido')
                     ->where('cvcl.ID_Alumno', '=', $alumnoId);
            })
            ->when($cursoId, function($query) use ($cursoId) {
                return $query->where('cvc.ID_Curso', $cursoId);
            })
            ->where('cvc.Tipo_Materia', 'c')
            ->where('cvc.Estado', 1)
            ->where('cvc.Fecha_Vencimiento', '<=', $hoy)
            ->when($ficl, function($query) use ($ficl) {
                return $query->where('cvc.Fecha', '>=', $ficl);
            })
            ->whereNull('cvcl.ID')
            ->count();
    }

    private function getUnreadWallNews(int $alumnoId, $cursoId = null, $ficl = null): int
    {
        return DB::table('tareas_materia_muro_detalle as tmmd')
            ->join('tareas_materia_muro as tmm', 'tmmd.ID', '=', 'tmm.ID')
            ->leftJoin('tareas_materia_muro_lecturas as tmml', function($join) use ($alumnoId) {
                $join->on('tmmd.ID', '=', 'tmml.ID_Muro_Detalle')
                     ->where('tmml.ID_Alumno', '=', $alumnoId);
            })
            ->when($cursoId, function($query) use ($cursoId) {
                return $query->where('tmmd.ID_Curso', $cursoId);
            })
            ->where('tmmd.Tipo_Usuario', 'D')
            ->where('tmmd.B', 0)
            ->when($ficl, function($query) use ($ficl) {
                return $query->where('tmm.Fecha', '>=', $ficl);
            })
            ->where('tmm.B', 0)
            ->whereNull('tmml.ID')
            ->count();
    }

    private function getTaskNews(int $alumnoId, $cicloId = null): int
    {
        return DB::table('tareas_consultas')
            ->where('ID_Alumno', $alumnoId)
            ->where('Tipo', 'D')
            ->where('Leido', 0)
            ->count();
    }

    private function getLatestCo2Measurement($cursoId = null): array
    {
        if (!$cursoId) {
            return [
                'estado' => 'Sin datos',
                'mensaje' => 'El alumno no tiene un curso asignado para leer CO2.'
            ];
        }

        $hoy = Carbon::today()->toDateString();
        
        $medicion = DB::table('mediciones_aulicas')
            ->select('Medicion', 'Hora', 'ID_Usuario')
            ->where('ID_Curso', $cursoId)
            ->where('Fecha', $hoy)
            ->orderByDesc('ID')
            ->first();

        if (!$medicion) {
            return [
                'estado' => 'Sin datos',
                'mensaje' => 'Aún no se registran mediciones de CO2 en el aula hoy.'
            ];
        }

        $medidor = DB::table('personal')->where('ID', $medicion->ID_Usuario)->first();
        $nombreMedidor = $medidor ? $medidor->Nombre . ' ' . $medidor->Apellido : 'Desconocido';
        $limite = 700;

        $excede = $medicion->Medicion > $limite;

        return [
            'estado' => $excede ? 'Alerta' : 'Normal',
            'medicion' => $medicion->Medicion,
            'hora' => $medicion->Hora,
            'medidor' => $nombreMedidor,
            'mensaje' => $excede 
                ? "La última medición arrojó {$medicion->Medicion} ppm. Se procedió a renovar el aire."
                : "La última medición arrojó {$medicion->Medicion} ppm. Parámetros normales."
        ];
    }

    private function getUpcomingEvents(Alumno $alumno): array
    {
        if ($alumno->ID_Situacion != 2) return [];

        $hoy = Carbon::today()->toDateString();

        return DB::table('agenda_comun')
            ->select('ID', 'ID_Categoria', 'ID_Tipo', 'Hora_Inicio', 'Hora_Fin', 'Campo_1', 'Campo_2', 'Campo_3', 'Actividad')
            ->where('Fecha_R', '>=', $hoy)
            ->where('B', 0)
            ->orderBy('Fecha_R')
            ->orderBy('Hora_Inicio')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getInasistencias(int $alumnoId): float
    {
        return 0; // TODO a futuro
    }
}