<?php

namespace App\Repositories\AppFamilias;

use App\Models\Alumno;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FamilyAulaRepository
{
    public function getAulasDisponibles($studentId)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) return [];

        $hoy = Carbon::now()->format('Y-m-d');
        
        $ciclo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->first();
            
        $cicloId = $ciclo ? $ciclo->ID : 0;
        $ficl = $ciclo ? $ciclo->IPT : '2000-01-01';

        // 1. Materias Normales
        $materiasCurso = DB::table('materias as m')
            ->leftJoin('personal as p', 'm.ID_Personal', '=', 'p.ID')
            ->where('m.ID_Curso', $alumno->ID_Curso)
            ->select('m.ID', 'm.Materia', 'p.Apellido', 'p.Nombre')
            ->get()
            ->map(function ($item) use ($studentId, $cicloId, $ficl, $hoy) {
                return [
                    'id' => $item->ID,
                    'tipo' => 'c',
                    'materia' => utf8_encode($item->Materia),
                    'docente' => utf8_encode("{$item->Apellido}, {$item->Nombre}"),
                    'novedades_total' => $this->countNovedades($studentId, $item->ID, 'c', $cicloId, $ficl, $hoy)
                ];
            });

        // 2. Materias Grupales
        $materiasGrupales = DB::table('materias_grupales as mg')
            ->join('grupos as g', 'mg.ID', '=', 'g.ID_Materia_Grupal')
            ->leftJoin('personal as p', 'mg.ID_Personal', '=', 'p.ID')
            ->where('g.ID_Alumno', $studentId)
            ->where('g.ID_Ciclo_Lectivo', $cicloId)
            ->where('mg.Estado', 0)
            ->select('mg.ID', 'mg.Materia', 'p.Apellido', 'p.Nombre')
            ->get()
            ->map(function ($item) use ($studentId, $cicloId, $ficl, $hoy) {
                return [
                    'id' => $item->ID,
                    'tipo' => 'g',
                    'materia' => utf8_encode($item->Materia),
                    'docente' => utf8_encode("{$item->Apellido}, {$item->Nombre}"),
                    'novedades_total' => $this->countNovedades($studentId, $item->ID, 'g', $cicloId, $ficl, $hoy)
                ];
            });

        return $materiasCurso->merge($materiasGrupales)->sortBy('materia')->values();
    }

    public function getDetalleAula($studentId, $materiaId, $tipoMateria)
    {
        $alumno = Alumno::find($studentId);
        $hoy = Carbon::now()->format('Y-m-d');
        
        $ciclo = DB::table('ciclo_lectivo')->where('ID_Nivel', $alumno->ID_Nivel)->where('Vigente', 'SI')->first();
        $cicloId = $ciclo ? $ciclo->ID : 0;

        // Clases
        $clases = DB::table('clases_virtuales')
            ->where('ID_Materia', $materiaId)
            ->where('Tipo_Materia', $tipoMateria)
            ->where('ID_Ciclo_Lectivo', $cicloId)
            ->where('Estado', 1)
            ->where('Fecha_Publicacion', '<=', $hoy)
            ->orderBy('Orden', 'desc')
            ->get();

        $resultado = [];

        foreach ($clases as $clase) {
            // Marcar clase como leída
            DB::table('clases_virtuales_envios')
                ->where('ID_Clase', $clase->ID)
                ->where('ID_Destinatario', $studentId)
                ->update([
                    'Leido' => 1,
                    'Fecha_Leido' => Carbon::now()->format('Y-m-d'),
                    'Hora_Leido' => Carbon::now()->format('H:i:s')
                ]);

            // Contenidos (Recursos)
            $contenidos = DB::table('clases_virtuales_contenidos')
                ->where('ID_Clase', $clase->ID)
                ->where('Estado', 1)
                ->orderBy('Orden')
                ->get()
                ->map(function ($c) use ($studentId) {
                    $extension = pathinfo($c->Archivo, PATHINFO_EXTENSION);
                    $leido = DB::table('clases_virtuales_contenidos_lecturas')
                        ->where('ID_Contenido', $c->ID)
                        ->where('ID_Alumno', $studentId)
                        ->exists();

                    return [
                        'id' => $c->ID,
                        'titulo' => utf8_encode($c->Titulo),
                        'descripcion' => utf8_encode($c->Descripcion),
                        'archivo' => $c->Archivo,
                        'tipo_recurso' => $c->ID_Tipo == 3 ? 'enlace' : (in_array(strtolower($extension), ['pdf']) ? 'pdf' : (in_array(strtolower($extension), ['mp4', 'avi']) ? 'video' : 'archivo')),
                        'progreso' => $leido ? 100 : 0,
                        'url_codigo' => $c->Code
                    ];
                });

            // Actividades (Tareas)
            $actividades = DB::table('clases_virtuales_actividades as cva')
                ->join('tareas_virtuales as tv', 'cva.ID_Tarea', '=', 'tv.ID')
                ->where('cva.ID_Clase', $clase->ID)
                ->where('tv.Cerrada', 0)
                ->orderBy('cva.Orden')
                ->select('cva.ID_Tipo', 'tv.*')
                ->get()
                ->map(function ($a) use ($studentId) {
                    $resuelta = DB::table('tareas_resoluciones')->where('ID_Tarea', $a->ID)->where('ID_Alumno', $studentId)->first();
                    
                    return [
                        'id_tarea' => $a->ID,
                        'tipo' => $a->ID_Tipo == 1 ? 'Tarea' : 'Foro',
                        'titulo' => utf8_encode($a->Titulo),
                        'fecha_publicacion' => Carbon::parse($a->Fecha)->format('Y-m-d'),
                        'fecha_vencimiento' => ($a->Fecha_Vencimiento && $a->Fecha_Vencimiento != '0000-00-00') ? Carbon::parse($a->Fecha_Vencimiento)->format('Y-m-d') : null,
                        'estado_resolucion' => $resuelta ? ($resuelta->Correccion ? 'Evaluado' : 'Entregado') : 'Pendiente'
                    ];
                });

            $resultado[] = [
                'clase_id' => $clase->ID,
                'orden' => $clase->Orden,
                'titulo' => utf8_encode($clase->Titulo),
                'guia_aprendizaje' => utf8_encode($clase->Guia_Ap),
                'recursos' => $contenidos,
                'actividades' => $actividades
            ];
        }

        return $resultado;
    }

    private function countNovedades($studentId, $materiaId, $tipo, $cicloId, $ficl, $hoy)
    {
        $tareasSinLeer = DB::table('tareas_envios as te')
            ->join('tareas_virtuales as tv', 'te.ID_Tarea', '=', 'tv.ID')
            ->where('te.ID_Destinatario', $studentId)
            ->where('te.Leido', 0)
            ->where('tv.ID_Materia', $materiaId)
            ->where('tv.Tipo_Materia', $tipo)
            ->where('tv.ID_Ciclo_Lectivo', $cicloId)
            ->where('tv.ID_Clase', 0)
            ->count();

        $recursosSinLeer = DB::table('clases_virtuales_contenidos as cvc')
            ->leftJoin('clases_virtuales_contenidos_lecturas as l', function($join) use ($studentId) {
                $join->on('cvc.ID', '=', 'l.ID_Contenido')->where('l.ID_Alumno', '=', $studentId);
            })
            ->where('cvc.ID_Materia', $materiaId)
            ->where('cvc.Tipo_Materia', $tipo)
            ->where('cvc.Visible', 1)
            ->where('cvc.ID_Clase', 0)
            ->where('cvc.Estado', '<=', 1)
            ->where('cvc.Fecha', '>=', $ficl)
            ->where('cvc.Fecha_Vencimiento', '<=', $hoy)
            ->whereNull('l.ID')
            ->count();

        return $tareasSinLeer + $recursosSinLeer;
    }

    public function getTareasGenerales($studentId, $materiaId, $tipoMateria)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) return [];

        $ciclo = DB::table('ciclo_lectivo')->where('ID_Nivel', $alumno->ID_Nivel)->where('Vigente', 'SI')->first();
        $cicloId = $ciclo ? $ciclo->ID : 0;

        // Obtener tareas generales (ID_Clase = 0)
        $tareas = DB::table('tareas_virtuales as tv')
            ->leftJoin('tareas_envios as te', function($join) use ($studentId) {
                $join->on('te.ID_Tarea', '=', 'tv.ID')
                     ->where('te.ID_Destinatario', '=', $studentId);
            })
            ->where('tv.ID_Materia', $materiaId)
            ->where('tv.Tipo_Materia', $tipoMateria)
            ->where('tv.ID_Clase', 0)
            ->where('tv.ID_Ciclo_Lectivo', $cicloId)
            ->where('tv.Cerrada', 0)
            ->orderBy('tv.Fecha', 'desc')
            ->select('tv.*', 'te.Leido as Leido_Envio')
            ->get()
            ->map(function ($tarea) use ($studentId) {
                $resuelta = DB::table('tareas_resoluciones')
                    ->where('ID_Tarea', $tarea->ID)
                    ->where('ID_Alumno', $studentId)
                    ->first();

                return [
                    'id' => $tarea->ID,
                    'titulo' => utf8_encode($tarea->Titulo),
                    'descripcion' => utf8_encode($tarea->Descripcion),
                    'fecha_publicacion' => Carbon::parse($tarea->Fecha)->format('Y-m-d'),
                    'fecha_vencimiento' => ($tarea->Fecha_Vencimiento && $tarea->Fecha_Vencimiento != '0000-00-00') ? Carbon::parse($tarea->Fecha_Vencimiento)->format('Y-m-d') : null,
                    'tipo' => $tarea->ID_Tipo == 1 ? 'Tarea' : 'Foro',
                    'estado_resolucion' => $resuelta ? ($resuelta->Correccion ? 'Evaluado' : 'Entregado') : 'Pendiente',
                    'leido' => (bool)$tarea->Leido_Envio,
                    'tiene_resolucion' => (bool)$resuelta
                ];
            });

        return $tareas;
    }

    public function getRecursosGenerales($studentId, $materiaId, $tipoMateria)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) return [];

        $hoy = Carbon::now()->format('Y-m-d');
        
        $ciclo = DB::table('ciclo_lectivo')->where('ID_Nivel', $alumno->ID_Nivel)->where('Vigente', 'SI')->first();
        $cicloId = $ciclo ? $ciclo->ID : 0;
        $ficl = $ciclo ? $ciclo->IPT : '2000-01-01';

        // Obtener recursos generales (ID_Clase = 0)
        $recursos = DB::table('clases_virtuales_contenidos as cvc')
            ->leftJoin('clases_virtuales_contenidos_lecturas as l', function($join) use ($studentId) {
                $join->on('cvc.ID', '=', 'l.ID_Contenido')
                     ->where('l.ID_Alumno', '=', $studentId);
            })
            ->where('cvc.ID_Materia', $materiaId)
            ->where('cvc.Tipo_Materia', $tipoMateria)
            ->where('cvc.ID_Clase', 0)
            ->where('cvc.Visible', 1)
            ->where('cvc.Estado', '<=', 1)
            ->where('cvc.Fecha', '>=', $ficl)
            ->where('cvc.Fecha_Vencimiento', '<=', $hoy)
            ->orderBy('cvc.Fecha', 'desc')
            ->select('cvc.*', 'l.ID as ID_Lectura')
            ->get()
            ->map(function ($recurso) {
                $extension = pathinfo($recurso->Archivo, PATHINFO_EXTENSION);
                $leido = (bool)$recurso->ID_Lectura;

                return [
                    'id' => $recurso->ID,
                    'titulo' => utf8_encode($recurso->Titulo),
                    'descripcion' => utf8_encode($recurso->Descripcion),
                    'archivo' => $recurso->Archivo,
                    'fecha_publicacion' => Carbon::parse($recurso->Fecha)->format('Y-m-d'),
                    'fecha_vencimiento' => ($recurso->Fecha_Vencimiento && $recurso->Fecha_Vencimiento != '0000-00-00') ? Carbon::parse($recurso->Fecha_Vencimiento)->format('Y-m-d') : null,
                    'tipo_recurso' => $recurso->ID_Tipo == 3 ? 'enlace' : (in_array(strtolower($extension), ['pdf']) ? 'pdf' : (in_array(strtolower($extension), ['mp4', 'avi', 'mov', 'wmv']) ? 'video' : 'archivo')),
                    'progreso' => $leido ? 100 : 0,
                    'url_codigo' => $recurso->Code,
                    'leido' => $leido
                ];
            });

        return $recursos;
    }
}
