<?php

namespace App\Repositories\AppFamilias;

use App\Models\TareaVirtual;
use App\Models\TareaResolucion;
use App\Models\TareaConsulta;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FamilyTaskRepository
{
    /**
     * Obtiene el listado de tareas filtrado por curso, burbuja y permisos
     */
    public function getStudentTasks($studentId)
    {
        $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
        if (!$alumno) return [];

        $ciclo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')->first();
        
        $cicloId = $ciclo ? $ciclo->ID : 0;

        // Obtener la burbuja (Agrupación) del alumno
        $idGrupo = DB::table('agrupaciones_detalle')
            ->where('ID_Alumno', $studentId)
            ->where('ID_Ciclo_Lectivo', $cicloId)
            ->value('ID_Grupo');

        $tareasRaw = TareaVirtual::with('docente')
            ->where('Envio', 1)
            ->where('Cerrada', 0)
            ->where('ID_Ciclo_Lectivo', $cicloId)
            ->orderBy('ID', 'desc')
            ->get();

        $tasks = [];
        foreach ($tareasRaw as $t) {
            if (!$this->isTaskVisible($t, $studentId, $alumno->ID_Curso, $idGrupo)) continue;

            $vencimiento = $this->getDueDate($t, $idGrupo);
            
            $tasks[] = [
                'id' => $t->ID,
                'fecha' => Carbon::parse($t->Fecha)->format('d/m/Y'),
                'materia' => $this->getMateriaName($t),
                'tipo' => ($t->Tipo == 1) ? 'Tarea' : 'Foro',
                'titulo' => $t->Titulo,
                'docente' => $t->docente ? $t->docente->Apellido . ' - ' . $t->docente->Nombre : 'N/A',
                'vencimiento' => $vencimiento['fecha'],
                'vencimiento_label' => $vencimiento['label'],
                'status' => $this->getTaskStatus($t->ID, $studentId),
                'alarma' => $vencimiento['alarma']
            ];
        }
        return $tasks;
    }

    /**
     * Obtiene el detalle completo de una tarea específica
     */
    public function getTaskDetail($taskId, $studentId)
    {
        $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
        if (!$alumno) return null;

        $ciclo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')->first();
        
        $cicloId = $ciclo ? $ciclo->ID : 0;

        // Obtener la burbuja del alumno
        $idGrupo = DB::table('agrupaciones_detalle')
            ->where('ID_Alumno', $studentId)
            ->where('ID_Ciclo_Lectivo', $cicloId)
            ->value('ID_Grupo');

        // Obtener la tarea con relaciones
        $tarea = TareaVirtual::with('docente')
            ->where('ID', $taskId)
            ->where('Envio', 1)
            ->where('Cerrada', 0)
            ->where('ID_Ciclo_Lectivo', $cicloId)
            ->first();

        if (!$tarea) return null;

        // Verificar si la tarea es visible para este alumno
        if (!$this->isTaskVisible($tarea, $studentId, $alumno->ID_Curso, $idGrupo)) {
            return null;
        }

        // Obtener resolución del alumno si existe
        $resolucion = TareaResolucion::where('ID_Tarea', $taskId)->where('ID_Alumno', $studentId)->first();

        // Obtener consultas del alumno
        $consultas = TareaConsulta::where('ID_Tarea', $taskId)
            ->where('ID_Alumno', $studentId)
            ->orderBy('ID', 'desc')
            ->get();

        $vencimiento = $this->getDueDate($tarea, $idGrupo);

        return [
            'id' => $tarea->ID,
            'fecha' => Carbon::parse($tarea->Fecha)->format('d/m/Y'),
            'materia' => $this->getMateriaName($tarea),
            'tipo' => ($tarea->Tipo == 1) ? 'Tarea' : 'Foro',
            'titulo' => $tarea->Titulo,
            'descripcion' => $tarea->Descripcion ?? '',
            'docente' => $tarea->docente ? $tarea->docente->Apellido . ' - ' . $tarea->docente->Nombre : 'N/A',
            'vencimiento' => $vencimiento['fecha'],
            'vencimiento_label' => $vencimiento['label'],
            'status' => $this->getTaskStatus($tarea->ID, $studentId),
            'alarma' => $vencimiento['alarma'],
            'resolucion' => $resolucion ? [
                'contenido' => $resolucion->Resolucion,
                'fecha' => $resolucion->Fecha,
                'hora' => $resolucion->Hora,
                'corregido' => ($resolucion->Correcion == 1)
            ] : null,
            'consultas' => $consultas->map(function($c) {
                return [
                    'id' => $c->ID,
                    'consulta' => $c->Consulta,
                    'fecha' => $c->Fecha,
                    'leido' => ($c->Leido == 1)
                ];
            })
        ];
    }

    /**
     * Guarda o actualiza la resolución del alumno (Lógica de concatenación legacy)
     */
    public function storeResolution($wallId, $studentId, $content)
    {
        $content = str_replace(["%", "'"], ["porc", " "], $content);
        $res = TareaResolucion::where('ID_Tarea', $wallId)->where('ID_Alumno', $studentId)->first();

        if (!$res) {
            return TareaResolucion::create([
                'ID_Tarea' => $wallId, 'ID_Alumno' => $studentId,
                'Resolucion' => $content, 'Fecha' => date('Y-m-d'), 'Hora' => date('H:i:s')
            ]);
        }

        // Si ya existe, concatenamos con la anterior según lógica legacy
        $nuevaResolucion = "<p>" . $res->Fecha . " (" . $res->Hora . "): " . $res->Resolucion . "<p>" . $content;
        
        $res->update([
            'Resolucion' => $nuevaResolucion, 'Fecha' => date('Y-m-d'),
            'Hora' => date('H:i:s'), 'Leido' => 0
        ]);

        DB::table('tareas_envios')->where('ID_Tarea', $wallId)->where('ID_Destinatario', $studentId)->update(['Resuelto' => 1]);

        return $res;
    }

    /**
     * Guarda una consulta al docente
     */
    public function storeQuery($wallId, $studentId, $content)
    {
        return TareaConsulta::create([
            'ID_Tarea' => $wallId, 'ID_Alumno' => $studentId, 'Tipo' => 'A',
            'ID_Usuario' => $studentId, 'Consulta' => $content, 'Fecha' => date('Y-m-d')
        ]);
    }

    // --- MÉTODOS PRIVADOS DE LÓGICA ---

    private function isTaskVisible($tarea, $studentId, $idCursoA, $idGrupo)
    {
        // Filtro por curso
        $idCursoT = ($tarea->ID_Materia > 0 && $tarea->Tipo_Materia != 'g') 
            ? DB::table('materias')->where('ID', $tarea->ID_Materia)->value('ID_Curso')
            : $tarea->ID_Curso;
        
        if ($idCursoT != $idCursoA && $tarea->Tipo_Materia != 'g') return false;

        // Filtro destinatarios específicos
        if ($tarea->Dest_Sel == 1) {
            if (!DB::table('tareas_envios')->where('ID_Destinatario', $studentId)->where('ID_Tarea', $tarea->ID)->exists()) return false;
        }

        // Filtro publicación por burbuja (Clase Virtual)
        if ($tarea->ID_Clase > 0) {
            $fPub = DB::table('clases_virtuales')->where('ID', $tarea->ID_Clase)->value('Fecha_Publicacion');
            if ($idGrupo) {
                $fBurbuja = DB::table('clases_virtuales_publicacion')->where('ID_Clase', $tarea->ID_Clase)->where('ID_Agrupacion', $idGrupo)->value('Fecha_Publicacion');
                if ($fBurbuja) $fPub = $fBurbuja;
            }
            if (date('Y-m-d') < $fPub) return false;
        }
        return true;
    }

    private function getDueDate($tarea, $idGrupo)
    {
        $fecha = $tarea->Fecha_Vencimiento;
        if ($idGrupo) {
            $v = DB::table('tareas_virtuales_vencimientos')->where('ID_Tarea', $tarea->ID)->where('ID_Agrupacion', $idGrupo)->first();
            if ($v) $fecha = $v->Fecha_Vencimiento;
        }

        if (!$fecha || $fecha == '0000-00-00') return ['fecha' => 'No posee', 'label' => '', 'alarma' => false];

        $dias = Carbon::parse($fecha)->diffInDays(Carbon::today(), false) * -1;
        $label = ($dias < 0) ? 'Vencida' : (($dias == 0) ? 'Vence hoy' : (($dias == 1) ? 'Vence mañana' : ''));
        
        return ['fecha' => Carbon::parse($fecha)->format('d/m/Y'), 'label' => $label, 'alarma' => ($dias <= 1)];
    }

    private function getTaskStatus($tareaId, $studentId)
    {
        $res = TareaResolucion::where('ID_Tarea', $tareaId)->where('ID_Alumno', $studentId)->first();
        if (!$res) return 'Pendiente';
        return ($res->Correcion == 1) ? 'Evaluado' : 'Pendiente de Evaluación';
    }

    private function getMateriaName($tarea)
    {
        if (empty($tarea->ID_Materia)) return DB::table('cursos')->where('ID', $tarea->ID_Curso)->value('Cursos');
        $tabla = ($tarea->Tipo_Materia == 'g') ? 'materias_grupales' : 'materias';
        return DB::table($tabla)->where('ID', $tarea->ID_Materia)->value('Materia');
    }
}
