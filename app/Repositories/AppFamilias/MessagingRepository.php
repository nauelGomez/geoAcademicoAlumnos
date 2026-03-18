<?php

namespace App\Repositories\AppFamilias;

use Illuminate\Support\Facades\DB;

class MessagingRepository
{
    public function getConversations($studentId, $familyId)
    {
        // 1. Obtener todas las cabeceras de conversación para este alumno y familia
        $conversaciones = DB::table('chat_codigo_conversaciones as ccc')
            ->where('ccc.ID_Familia', $familyId)
            ->where('ccc.ID_Alumno', $studentId)
            ->orderBy('ccc.Fecha', 'desc')
            ->get();

        return $conversaciones->map(function($conv) use ($familyId) {
            // Buscar el último mensaje de esta charla
            $lastMsg = DB::table('chat')
                ->where('Codigo', $conv->Codigo)
                ->orderBy('ID', 'desc')
                ->first();

            // Buscar nombre del destinatario (Docente o Grupo)
            // Cambié chat_groups por chat_grupos según tu legacy
            $dest = DB::table('chat_grupos')->where('ID', $conv->ID_Docente)->first(); 
            if (!$dest) {
                $p = DB::table('personal')->where('ID', $conv->ID_Docente)->first();
                $nombre = "Prof. " . ($p->Apellido ?? 'N/A') . ", " . ($p->Nombre ?? '');
            } else {
                $nombre = $dest->Nombre;
            }

            // Contar mensajes sin leer para la familia (Tipo_Destinatario = 2)
            $noLeidos = DB::table('chat')
                ->where('Codigo', $conv->Codigo)
                ->where('ID_Destinatario', $familyId)
                ->where('Tipo_Destinatario', 2)
                ->where('Leido', 0)
                ->count();

            return [
                'codigo' => $conv->Codigo,
                'destinatario_nombre' => $nombre,
                'ultimo_mensaje' => $lastMsg ? $lastMsg->Mensaje : 'Sin mensajes',
                'fecha' => $lastMsg ? date('d/m/Y H:i', strtotime($lastMsg->Fecha . ' ' . $lastMsg->Hora)) : '',
                'no_leidos' => $noLeidos
            ];
        });
    }

    public function sendMessage($data)
    {
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        
        $studentId = $data['id_alumno'];
        $familyId  = $data['id_familia'];
        $destId    = $data['id_destinatario'];
        $codigo    = $data['codigo'] ?? null;

        // --- FIX: ASEGURAR QUE EXISTE LA CABECERA DE CONVERSACIÓN ---
        $exists = false;
        if ($codigo) {
            $exists = DB::table('chat_codigo_conversaciones')
                ->where('Codigo', $codigo)
                ->exists();
        }

        if (!$exists) {
            if (!$codigo) $codigo = str_random(10);
            
            DB::table('chat_codigo_conversaciones')->insert([
                'ID_Docente' => $destId,
                'ID_Familia' => $familyId,
                'ID_Alumno'  => $studentId,
                'Codigo'     => $codigo,
                'Fecha'      => $fecha,
                'Hora'       => $hora
            ]);
        }
        // ----------------------------------------------------------

        $supervision = DB::table('nivel_parametros')->where('ID_Nivel', $data['id_nivel'])->value('Supervision_Mensajeria');
        $publico = ($supervision == 1) ? 0 : 1;

        $grupo = DB::table('chat_grupos')->where('ID', $destId)->first();
        if ($grupo && $grupo->Referencia == 'PR') {
            $curso = DB::table('cursos')->where('ID', $data['id_curso'])->first();
            $preceptores = array_filter([$curso->ID_Preceptor, $curso->ID_Pareja, $curso->ID_Pareja2]);
            foreach ($preceptores as $pid) {
                $this->insertChatMessage($fecha, $hora, $familyId, $pid, $data['mensaje'], $codigo, $studentId, $data['id_nivel'], $publico);
            }
        } else {
            $this->insertChatMessage($fecha, $hora, $familyId, $destId, $data['mensaje'], $codigo, $studentId, $data['id_nivel'], $publico);
        }

        return $codigo;
    }

    private function insertChatMessage($f, $h, $rem, $dest, $msj, $cod, $al, $niv, $p)
    {
        return DB::table('chat')->insert([
            'Fecha' => $f, 'Hora' => $h,
            'ID_Remitente' => $rem, 'Tipo_Remitente' => 2,
            'ID_Destinatario' => $dest, 'Tipo_Destinatario' => 1,
            'Mensaje' => $msj, 'Codigo' => $cod,
            'ID_Alumno' => $al, 'ID_Nivel' => $niv, 'P' => $p
        ]);
    }

    public function getMessages($code, $familyId)
    {
        DB::table('chat')
            ->where('Codigo', $code)
            ->where('ID_Destinatario', $familyId)
            ->where('Tipo_Destinatario', 2)
            ->update(['Leido' => 1, 'Fecha_Leido' => date('Y-m-d'), 'Hora_Leido' => date('H:i:s')]);

        return DB::table('chat')
            ->where('Codigo', $code)
            ->orderBy('ID', 'asc')
            ->get()
            ->map(function($m) {
                return [
                    'id' => $m->ID,
                    'mensaje' => $m->Mensaje,
                    'fecha' => date('d/m/Y', strtotime($m->Fecha)),
                    'hora' => $m->Hora,
                    'es_mio' => ($m->Tipo_Remitente == 2),
                    'remitente_id' => $m->ID_Remitente
                ];
            });
    }

    public function getAvailableRecipients($studentId)
    {
        $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
        if (!$alumno) return [];

        $grupos = DB::table('chat_grupos')->where('ID_Nivel', $alumno->ID_Nivel)->get();
        $recipients = $grupos->map(function($g) {
            return ['id' => $g->ID, 'nombre' => $g->Nombre, 'tipo' => 'GRUPO', 'referencia' => $g->Referencia];
        })->toArray();

        $docentesMat = DB::table('materias as m')
            ->join('personal as p', 'm.ID_Personal', '=', 'p.ID')
            ->where('m.ID_Curso', $alumno->ID_Curso)
            ->where('m.Mensajeria', 1)
            ->select('p.ID', 'p.Apellido', 'p.Nombre', 'm.Materia')
            ->get();

        foreach ($docentesMat as $d) {
            $recipients[] = ['id' => $d->ID, 'nombre' => "Prof. {$d->Apellido} ({$d->Materia})", 'tipo' => 'DOCENTE'];
        }

        return $recipients;
    }
}
