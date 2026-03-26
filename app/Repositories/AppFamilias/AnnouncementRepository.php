<?php

namespace App\Repositories\AppFamilias;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnnouncementRepository
{
    public function getStudentAnnouncements($studentId, $familyEmail)
    {
        // 1. Obtener Comunicados Generales/Grupales
        $comunicados = DB::table('comunicados_detalle as cd')
            ->join('comunicados as c', 'cd.ID_Comunicado', '=', 'c.ID')
            ->where('cd.ID_Destinatario', $studentId)
            ->where('cd.MailD', $familyEmail)
            ->select('cd.Aleatorio as code', 'cd.Leido', 'c.*', DB::raw('1 as source_type'))
            ->get();

        // 2. Obtener Notificaciones Personales
        $notificaciones = DB::table('notificaciones_enviadas')
            ->where('ID_Alumno', $studentId)
            ->select('Aleatorio as code', 'Leido', 'Fecha', 'Titulo', 'Mensaje as Descripcion', 'ID_Personal as ID_Autor', 'Adjunto', DB::raw('2 as source_type'))
            ->get();

        $merged = [];

        // Procesar Comunicados
        foreach ($comunicados as $c) {
            $merged[] = [
                'code' => $c->code,
                'fecha' => $c->Fecha,
                'fecha_fmt' => date('d/m/Y', strtotime($c->Fecha)),
                'titulo' => ($c->Fecha >= '2025-08-02') ? utf8_encode($c->Titulo) : $c->Titulo,
                'remitente' => $this->getRemitente($c),
                'leido' => (bool)$c->Leido,
                'tipo' => 1 // Tipo Comunicado
            ];
        }

        // Procesar Notificaciones
        foreach ($notificaciones as $n) {
            if (empty($n->code)) continue;
            
            $autor = DB::table('personal')->where('ID', $n->ID_Autor)->first();
            $merged[] = [
                'code' => $n->code,
                'fecha' => $n->Fecha,
                'fecha_fmt' => date('d/m/Y', strtotime($n->Fecha)),
                'titulo' => ($n->Fecha >= '2025-08-02') ? utf8_encode($n->Titulo) : $n->Titulo,
                'remitente' => "Prof. " . ($autor->Apellido ?? 'Institución'),
                'leido' => ($n->Leido === 'SI' || $n->Leido == 1),
                'tipo' => 2 // Tipo Notificación
            ];
        }

        // Ordenar por fecha DESC
        usort($merged, function($a, $b) {
            return strcmp($b['fecha'], $a['fecha']);
        });

        return $merged;
    }

    public function getAnnouncementDetail($studentId, $tipo, $code, $familyId)
    {
        $hoy = date('Y-m-d');
        $hora = date('H:i:s');
        $data = null;
        $remitente = '';

        if ($tipo == 1) { // Comunicado
            $detalle = DB::table('comunicados_detalle')->where('Aleatorio', $code)->first();
            if (!$detalle) return null;

            // Marcar lectura
            DB::table('comunicados_detalle')->where('ID', $detalle->ID)->update([
                'Leido' => 1, 'Fecha_Leido' => $hoy, 'Hora_Leido' => $hora, 'Envio' => 1
            ]);

            $com = DB::table('comunicados')->where('ID', $detalle->ID_Comunicado)->first();
            $remitente = $this->getRemitente($com);
            $data = $com;
        } else { // Notificación
            $notif = DB::table('notificaciones_enviadas')->where('Aleatorio', $code)->first();
            if (!$notif) return null;

            // Marcar lectura
            DB::table('notificaciones_enviadas')->where('ID', $notif->ID)->update([
                'Leido' => 1, 'Fecha_Leido' => $hoy, 'Hora_Leido' => $hora, 'ID_Lectura' => $familyId, 'Enviada' => 1
            ]);

            $autor = DB::table('personal')->where('ID', $notif->ID_Personal)->first();
            $remitente = "Prof. " . ($autor->Apellido ?? "") . ", " . ($autor->Nombre ?? "");
            $data = (object)[
                'Titulo' => $notif->Titulo,
                'Descripcion' => $notif->Mensaje,
                'Fecha' => $notif->Fecha,
                'Hora' => date('H:i', strtotime($notif->Fecha)), // Fallback
                'Adjunto' => $notif->Adjunto
            ];
        }

        $institucion = DB::table('institucion')->first();

        return [
            'titulo' => ($data->Fecha >= '2025-08-02') ? utf8_encode($data->Titulo) : $data->Titulo,
            'descripcion' => ($data->Fecha >= '2025-08-02') ? utf8_encode($data->Descripcion) : $data->Descripcion,
            'fecha' => date('d/m/Y', strtotime($data->Fecha)),
            'hora' => $data->Hora ?? '',
            'remitente' => $remitente,
            'adjunto_url' => $data->Adjunto ? "https://geoeducacion.com.ar/{$institucion->Carpeta}/comunicados/{$data->Adjunto}" : null
        ];
    }

    private function getRemitente($c) {
        if ($c->Tipo == 'I') return 'Institucional';
        if ($c->Tipo == 'DI') return 'Dirección';
        
        $autor = DB::table('personal')->where('ID', $c->ID_Autor)->first();
        $apellido = $autor->Apellido ?? '';
        
        if ($c->ID_Materia >= 1) {
            $materia = DB::table('materias_grupales')->where('ID', $c->ID_Materia)->value('Materia');
            return "Prof. $apellido ($materia)";
        }
        $curso = DB::table('cursos')->where('ID', $c->ID_Curso)->value('Cursos');
        return "Prof. $apellido ($curso)";
    }
}
