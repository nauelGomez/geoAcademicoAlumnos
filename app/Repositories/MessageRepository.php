<?php

namespace App\Repositories;

use App\Models\Alumno;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MessageRepository
{
    public function getConversations($studentId, $familiaId = null)
    {
        $query = ChatConversation::where('ID_Alumno', $studentId);

        if ($familiaId) {
            $query->where('ID_Familia', $familiaId);
        }

        $conversaciones = $query->get();
        $resultado = [];

        foreach ($conversaciones as $conv) {
            $ultimoMensaje = DB::table('chat')
                ->where('Codigo', $conv->Codigo)
                ->orderBy('ID', 'desc')
                ->first();

            if (!$ultimoMensaje) {
                continue;
            }

            $querySinLeer = DB::table('chat')
                ->where('Codigo', $conv->Codigo)
                ->where('Tipo_Destinatario', 2)
                ->where('Leido', 0);

            if ($familiaId) {
                $querySinLeer->where('ID_Destinatario', $familiaId);
            }

            $sinLeer = $querySinLeer->count();

            $nombreDocente = 'Desconocido';
            $grupo = DB::table('chat_grupos')->where('ID', $conv->ID_Docente)->first();

            if ($grupo) {
                $nombreDocente = $grupo->Nombre;
            } else {
                $profe = DB::table('personal')->where('ID', $conv->ID_Docente)->first();
                if ($profe) {
                    $nombreDocente = "Prof. {$profe->Apellido}, {$profe->Nombre}";
                }
            }

            $resultado[] = [
                'codigo' => $conv->Codigo,
                'docente' => $nombreDocente,
                'ultimo_mensaje' => $ultimoMensaje->Mensaje,
                'fecha_raw' => $ultimoMensaje->Fecha . ' ' . $ultimoMensaje->Hora,
                'fecha_um' => date('d/m/Y', strtotime($ultimoMensaje->Fecha)),
                'hora_um' => $ultimoMensaje->Hora,
                'sin_leer' => $sinLeer,
                'id_familia' => $conv->ID_Familia,
            ];
        }

        usort($resultado, function ($a, $b) {
            return strtotime($b['fecha_raw']) - strtotime($a['fecha_raw']);
        });

        return array_map(function ($item) {
            unset($item['fecha_raw']);
            return $item;
        }, $resultado);
    }

    public function getAvailableRecipients($studentId)
    {
        $alumno = Alumno::findOrFail($studentId);
        $destinatarios = [];

        $grupos = DB::table('chat_grupos')->where('ID_Nivel', $alumno->ID_Nivel)->get();

        foreach ($grupos as $grupo) {
            $ref = $grupo->Referencia;

            if (in_array($ref, ['DI', 'PR', 'EQ'])) {
                $destinatarios[] = ['id' => $grupo->ID, 'nombre' => $grupo->Nombre, 'tipo' => 'grupo'];
            }

            if ($ref == 'PF') {
                $materias = DB::table('materias')
                    ->join('personal', function ($join) {
                        $join->on('materias.ID_Personal', '=', 'personal.ID')
                            ->orOn('materias.ID_Adjunto', '=', 'personal.ID');
                    })
                    ->where('materias.ID_Curso', $alumno->ID_Curso)
                    ->where('materias.Mensajeria', 1)
                    ->where('personal.Estado', 'H')
                    ->select('personal.ID', 'personal.Apellido', 'personal.Nombre', 'materias.Materia')
                    ->get();

                foreach ($materias as $mat) {
                    $destinatarios[] = [
                        'id' => $mat->ID,
                        'nombre' => "Prof. {$mat->Apellido}, {$mat->Nombre} ({$mat->Materia})",
                        'tipo' => 'docente',
                    ];
                }
            }

            if (in_array($ref, ['MG', 'MI'])) {
                $cursoInfo = DB::table('cursos')->where('ID', $alumno->ID_Curso)->first();
                if ($cursoInfo) {
                    $idsPreceptores = array_filter([$cursoInfo->ID_Preceptor, $cursoInfo->ID_Pareja, $cursoInfo->ID_Pareja2]);

                    if (!empty($idsPreceptores)) {
                        $preceptores = DB::table('personal')
                            ->whereIn('ID', $idsPreceptores)
                            ->where('Estado', 'H')
                            ->get();

                        foreach ($preceptores as $prec) {
                            $destinatarios[] = [
                                'id' => $prec->ID,
                                'nombre' => "Prof. {$prec->Apellido}, {$prec->Nombre}",
                                'tipo' => 'docente',
                            ];
                        }
                    }
                }
            }
        }

        return collect($destinatarios)->unique('id')->values()->all();
    }

    public function getChatDetails($codigo, $studentId, $familiaId)
    {
        ChatMessage::where('Codigo', $codigo)
            ->where('ID_Destinatario', $familiaId)
            ->where('Tipo_Destinatario', 2)
            ->where('Leido', 0)
            ->update([
                'Leido' => 1,
                'Fecha_Leido' => date('Y-m-d'),
                'Hora_Leido' => date('H:i:s'),
            ]);

        $conv = ChatConversation::where('Codigo', $codigo)->first();
        $nombreProfesor = 'Desconocido';
        $foto = 'usuario.png';

        if ($conv) {
            $grupo = DB::table('chat_grupos')->where('ID', $conv->ID_Docente)->first();
            if ($grupo) {
                $nombreProfesor = $grupo->Nombre;
            } else {
                $profe = DB::table('personal')->where('ID', $conv->ID_Docente)->first();
                if ($profe) {
                    $nombreProfesor = "Prof. {$profe->Apellido}, {$profe->Nombre}";
                    $foto = $profe->PIC ?: 'usuario.png';
                }
            }
        }

        $mensajes = ChatMessage::where('Codigo', $codigo)
            ->orderBy('ID', 'asc')
            ->get()
            ->map(function ($msj) use ($familiaId) {
                $enviadoPorMi = ($msj->ID_Remitente == $familiaId && $msj->Tipo_Remitente == 2);

                return [
                    'id' => $msj->ID,
                    'mensaje' => $msj->Mensaje,
                    'fecha' => date('d/m/Y', strtotime($msj->Fecha)),
                    'hora' => $msj->Hora,
                    'enviado_por_mi' => $enviadoPorMi,
                ];
            });

        return [
            'codigo' => $codigo,
            'profesor' => $nombreProfesor,
            'foto_profesor' => "https://geoeducacion.com.ar/geo/imagenes/usuarios/{$foto}",
            'mensajes' => $mensajes,
        ];
    }

    public function startConversation($studentId, $familiaId, $data)
    {
        $destinatarioId = $data['destinatario'];
        $texto = trim($data['mensaje']);
        $hoy = date('Y-m-d');
        $ahora = date('H:i:s');

        $conv = ChatConversation::where('ID_Familia', $familiaId)
            ->where('ID_Alumno', $studentId)
            ->where('ID_Docente', $destinatarioId)
            ->first();

        if (!$conv) {
            $codigo = Str::random(10);

            $conv = ChatConversation::create([
                'Codigo' => $codigo,
                'ID_Familia' => $familiaId,
                'ID_Alumno' => $studentId,
                'ID_Docente' => $destinatarioId,
                'Fecha' => $hoy,
                'Hora' => $ahora,
            ]);
        } else {
            $codigo = $conv->Codigo;
            $conv->update(['Fecha' => $hoy, 'Hora' => $ahora]);
        }

        $mensaje = ChatMessage::create([
            'Codigo' => $codigo,
            'Mensaje' => $texto,
            'Fecha' => $hoy,
            'Hora' => $ahora,
            'ID_Remitente' => $familiaId,
            'Tipo_Remitente' => 2,
            'ID_Destinatario' => $destinatarioId,
            'Tipo_Destinatario' => 1,
            'Leido' => 0,
        ]);

        return ['codigo' => $codigo, 'mensaje' => $mensaje];
    }

    public function replyMessage($codigo, $studentId, $familiaId, $data)
    {
        $texto = trim($data['mensaje']);
        $hoy = date('Y-m-d');
        $ahora = date('H:i:s');

        $conv = ChatConversation::where('Codigo', $codigo)->firstOrFail();
        $conv->update(['Fecha' => $hoy, 'Hora' => $ahora]);

        $mensaje = ChatMessage::create([
            'Codigo' => $codigo,
            'Mensaje' => $texto,
            'Fecha' => $hoy,
            'Hora' => $ahora,
            'ID_Remitente' => $familiaId,
            'Tipo_Remitente' => 2,
            'ID_Destinatario' => $conv->ID_Docente,
            'Tipo_Destinatario' => 1,
            'Leido' => 0,
        ]);

        return $mensaje;
    }
}
