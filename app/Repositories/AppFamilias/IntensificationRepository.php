<?php

namespace App\Repositories\AppFamilias;

use Illuminate\Support\Facades\DB;

class IntensificationRepository
{
    public function getIntensificationTasks($studentId)
    {
        $hoy = date("Y-m-d");

        // 1. Obtener datos básicos del alumno
        $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
        if (!$alumno) return [];

        // 2. Buscar el ciclo de intensificación (Hardcodeado a 2020 según el legacy)
        $cicloInt = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Ciclo_lectivo', '2020')
            ->first();

        if (!$cicloInt) return [];

        // 3. Obtener el curso que tenía el alumno en ese ciclo específico
        $cursoAntiguo = DB::table('alumnos_cla')
            ->where('ID_Alumno', $studentId)
            ->where('ID_Ciclo_Lectivo', $cicloInt->ID)
            ->value('ID_Curso');

        if (!$cursoAntiguo) return [];

        // 4. Buscar qué materias adeuda el alumno (ID_Estado <= 2)
        $materiasAdeudadas = DB::table('procesos_valoracion_final_alumnos')
            ->where('ID_Alumno', $studentId)
            ->where('ID_Estado', '<=', 2)
            ->pluck('ID_Materia')
            ->toArray();

        if (empty($materiasAdeudadas)) return [];

        // 5. Traer las tareas virtuales de ese ciclo y de esas materias
        $tareas = DB::table('tareas_virtuales as tv')
            ->leftJoin('materias as m', 'tv.ID_Materia', '=', 'm.ID')
            ->leftJoin('personal as p', 'tv.ID_Usuario', '=', 'p.ID')
            ->where('tv.Envio', 1)
            ->where('tv.Cerrada', 0)
            ->where('tv.ID_Ciclo_Lectivo', $cicloInt->ID)
            ->whereIn('tv.ID_Materia', $materiasAdeudadas)
            ->select(
                'tv.*', 
                'm.Materia as Materia_Nombre', 
                'p.Apellido as Docente_Apellido', 
                'p.Nombre as Docente_Nombre'
            )
            ->orderBy('tv.ID', 'desc')
            ->get();

        $resultado = [];

        foreach ($tareas as $t) {
            // Verificar si la tarea es para destinatarios seleccionados
            if ($t->Dest_Sel == 1) {
                $enviada = DB::table('tareas_envios')
                    ->where('ID_Destinatario', $studentId)
                    ->where('ID_Tarea', $t->ID)
                    ->exists();
                if (!$enviada) continue;
            }

            // Lógica de Status (Leído, Resuelto, Corregido)
            $status = $this->getTaskStatus($t->ID, $studentId);

            // Lógica de Vencimiento y Alarma
            $vencimientoData = $this->getVencimiento($t, $hoy);

            $resultado[] = [
                'id' => $t->ID,
                'fecha' => date('d/m/Y', strtotime($t->Fecha)),
                'materia' => $t->Materia_Nombre ?? 'General',
                'tipo' => ($t->Tipo == 1) ? 'Tarea' : 'Foro',
                'titulo' => $t->Titulo,
                'docente' => "{$t->Docente_Apellido}, {$t->Docente_Nombre}",
                'vencimiento' => $vencimientoData['fecha_fmt'],
                'alarma' => $vencimientoData['alarma'],
                'status' => $status,
                'link_type' => ($t->Tipo == 1) ? 'ver_tarea' : 'ver_foro'
            ];
        }

        return $resultado;
    }

    private function getTaskStatus($taskId, $studentId)
    {
        $envio = DB::table('tareas_envios')
            ->where('ID_Destinatario', $studentId)
            ->where('ID_Tarea', $taskId)
            ->first();

        if (!$envio) return 'Pendiente';

        $resolucion = DB::table('tareas_resoluciones')
            ->where('ID_Alumno', $studentId)
            ->where('ID_Tarea', $taskId)
            ->first();

        if (!$resolucion) return 'Sin Entregar';

        if ($resolucion->Correccion == 1) return 'Evaluado';

        return 'Entregado (Pendiente Evaluación)';
    }

    private function getVencimiento($t, $hoy)
    {
        if (!$t->Fecha_Vencimiento || $t->Fecha_Vencimiento == '0000-00-00') {
            return ['fecha_fmt' => 'No posee', 'alarma' => ''];
        }

        $fv = $t->Fecha_Vencimiento;
        $dias = (strtotime($fv) - strtotime($hoy)) / 86400;

        $alarma = '';
        if ($fv < $hoy) $alarma = 'Vencida';
        elseif ($dias == 0) $alarma = 'Vence hoy';
        elseif ($dias == 1) $alarma = 'Vence mañana';

        return [
            'fecha_fmt' => date('d/m/Y', strtotime($fv)),
            'alarma' => $alarma
        ];
    }
}
