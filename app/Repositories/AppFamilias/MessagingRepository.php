<?php

namespace App\Repositories\AppFamilias;

use Illuminate\Support\Facades\DB;

class MessagingRepository
{
    public function getConversations($studentId, $familyId)
    {
        // 1. Obtener todas las cabeceras de conversación
        $conversaciones = DB::table('chat_codigo_conversaciones as ccc')
            ->where('ccc.ID_Familia', $familyId)
            ->where('ccc.ID_Alumno', $studentId)
            ->orderBy('ccc.Fecha', 'desc')
            ->get();

        return $conversaciones->map(function($conv) use ($familyId) {
            // Buscar último mensaje
            $lastMsg = DB::table('chat')
                ->where('Codigo', $conv->Codigo)
                ->orderBy('ID', 'desc')
                ->first();

            // Buscar nombre del funcionario o grupo
            $dest = DB::table('chat_groups')->where('ID', $conv->ID_Docente)->first(); // Tabla chat_groups o chat_grupos según legacy
            if (!$dest) {
                $p = DB::table('personal')->where('ID', $conv->ID_Docente)->first();
                $nombre = "Prof. " . ($p->Apellido ?? 'N/A') . ", " . ($p->Nombre ?? '');
            } else {
                $nombre = $dest->Nombre;
            }

            $noLeidos = DB::table('chat')
                ->where('Codigo', $conv->Codigo)
                ->where('ID_Destinatario', $familyId)
                ->where('Tipo_Destinatario', 2)
                ->where('Leido', 0)
                ->count();

            return [
                'codigo' => $conv->Codigo,
                'destinatario_nombre' => $nombre,
                'ultimo_mensaje' => $lastMsg ? $lastMsg->Mensaje : '',
                'fecha' => $lastMsg ? date('d/m/Y H:i', strtotime($lastMsg->Fecha . ' ' . $lastMsg->Hora)) : '',
                'no_leidos' => $noLeidos
            ];
        });
    }

    public function getMessages($code, $familyId)
    {
        // Marcar como leídos
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
                    'es_mio' => ($m->Tipo_Remitente == 2), // 2 = Familia
                    'remitente_id' => $m->ID_Remitente
                ];
            });
    }

    public function getAvailableRecipients($studentId)
    {
        $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
        $ciclo = DB::table('ciclo_lectivo')->where('ID_Nivel', $alumno->ID_Nivel)->where('Vigente', 'SI')->first();
        
        // 1. Grupos Institucionales (DI, PR, EQ)
        $grupos = DB::table('chat_grupos')->where('ID_Nivel', $alumno->ID_Nivel)->get();
        
        $recipients = $grupos->map(function($g) {
            return ['id' => $g->ID, 'nombre' => $g->Nombre, 'tipo' => 'GRUPO', 'referencia' => $g->Referencia];
        })->toArray();

        // 2. Docentes del Alumno (Materias Normales)
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

    public function sendMessage($data)
    {
        $fecha = date('Y-m-d');
        $hora = date('H:i:s');
        
        // Determinar si es público (supervisado)
        $supervision = DB::table('nivel_parametros')->where('ID_Nivel', $data['id_nivel'])->value('Supervision_Mensajeria');
        $publico = ($supervision == 1) ? 0 : 1;

        // Si no hay código, se genera uno nuevo
        $codigo = $data['codigo'];
        if (!$codigo) {
            $codigo = str_random(10);
            DB::table('chat_codigo_conversaciones')->insert([
                'ID_Docente' => $data['id_destinatario'],
                'ID_Familia' => $data['id_familia'],
                'ID_Alumno'  => $data['id_alumno'],
                'Codigo'     => $codigo,
                'Fecha'      => $fecha,
                'Hora'       => $hora
            ]);
        }

        // Lógica especial de Preceptoría (PR)
        $grupo = DB::table('chat_grupos')->where('ID', $data['id_destinatario'])->first();
        if ($grupo && $grupo->Referencia == 'PR') {
            $curso = DB::table('cursos')->where('ID', $data['id_curso'])->first();
            $preceptores = array_filter([$curso->ID_Preceptor, $curso->ID_Pareja, $curso->ID_Pareja2]);
            
            foreach ($preceptores as $pid) {
                $this->insertChatMessage($fecha, $hora, $data['id_familia'], $pid, $data['mensaje'], $codigo, $data['id_alumno'], $data['id_nivel'], $publico);
            }
        } else {
            $this->insertChatMessage($fecha, $hora, $data['id_familia'], $data['id_destinatario'], $data['mensaje'], $codigo, $data['id_alumno'], $data['id_nivel'], $publico);
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
}
