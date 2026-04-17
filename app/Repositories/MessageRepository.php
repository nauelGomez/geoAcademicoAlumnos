<?php

namespace App\Repositories;

use App\Models\Alumno;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class MessageRepository
{
    /**
     * Helper para resolver el ID de familia desde la base de datos maestra (Multitenant)
     */
    public function getFamilyIdFromAsociacion($studentId, $institutionId)
    {
        return DB::connection('mysql_gral')
            ->table('asociaciones')
            ->where('ID_Alumno', $studentId)
            ->where('ID_Institucion', $institutionId)
            ->value('ID_Familia');
    }

    public function getConversations($studentId, $familiaId)
    {
        $conversaciones = ChatConversation::where('ID_Alumno', $studentId)
            ->where('ID_Familia', $familiaId)
            ->get();

        $resultado = [];

        foreach ($conversaciones as $conv) {
            $ultimoMensaje = DB::table('chat')
                ->where('Codigo', $conv->Codigo)
                ->orderBy('ID', 'desc')
                ->first();

            if (!$ultimoMensaje) {
                continue;
            }

            $sinLeer = DB::table('chat')
                ->where('Codigo', $conv->Codigo)
                ->where('ID_Destinatario', $familiaId)
                ->where('Tipo_Destinatario', 2)
                ->where('Leido', 0)
                ->count();

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
            'id_docente' => $conv->ID_Docente, // La que agregamos recién
            'docente' => $nombreDocente,
            'ultimo_mensaje' => $ultimoMensaje->Mensaje,
            'fecha_raw' => $ultimoMensaje->Fecha . ' ' . $ultimoMensaje->Hora, // <-- ¡Me había comido esta línea!
            'fecha_display' => date('d/m/Y', strtotime($ultimoMensaje->Fecha)),
            'hora_um' => $ultimoMensaje->Hora,
            'sin_leer' => $sinLeer,
            'id_familia' => $conv->ID_Familia,
        ];
        }

        // Ordenar por fecha y hora descendente (El más nuevo arriba)
        usort($resultado, function ($a, $b) {
            return strcmp($b['fecha_raw'], $a['fecha_raw']);
        });

        // Limpiar data innecesaria para el frontend
        return array_map(function ($item) {
            unset($item['fecha_raw']);
            return $item;
        }, $resultado);
    }

    public function getAvailableRecipients($studentId)
    {
        $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
        if (!$alumno) return [];

        $destinatarios = [];

        // 1. Grupos Institucionales (Dirección, Equipo, etc.)
        $grupos = DB::table('chat_grupos')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->whereIn('Referencia', ['DI', 'PR', 'EQ'])
            ->get();

        foreach ($grupos as $grupo) {
            $destinatarios[] = [
                'id' => $grupo->ID,
                'nombre' => $grupo->Nombre,
                'tipo' => 'GRUPO',
            ];
        }

        // 2. Docentes (Materias Normales - Titulares y Adjuntos)
        $materias = DB::table('materias as m')
            ->join('personal as p', function ($join) {
                $join->on('m.ID_Personal', '=', 'p.ID')
                     ->orOn('m.ID_Adjunto', '=', 'p.ID');
            })
            ->where('m.ID_Curso', $alumno->ID_Curso)
            ->where('m.Mensajeria', 1)
            ->where('p.Estado', 'H') // H = Habilitado
            ->select('p.ID', 'p.Apellido', 'p.Nombre', 'm.Materia')
            ->get();

        foreach ($materias as $mat) {
            $destinatarios[] = [
                'id' => $mat->ID,
                'nombre' => "Prof. {$mat->Apellido}, {$mat->Nombre}",
                'tipo' => $mat->Materia,
            ];
        }

        // 3. Docentes (Materias Grupales)
        $ciclo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->first();

        if ($ciclo) {
            $materiasGrupales = DB::table('materias_grupales as mg')
                ->join('grupos as g', 'g.ID_Materia_Grupal', '=', 'mg.ID')
                ->join('personal as p', function ($join) {
                    $join->on('mg.ID_Personal', '=', 'p.ID')
                         ->orOn('mg.ID_Adjunto', '=', 'p.ID');
                })
                ->where('g.ID_Alumno', $studentId)
                ->where('mg.ID_Ciclo_Lectivo', $ciclo->ID)
                ->where('mg.Estado', 0) // 0 = Activa
                ->where('p.Estado', 'H')
                ->select('p.ID', 'p.Apellido', 'p.Nombre', 'mg.Materia')
                ->get();

            foreach ($materiasGrupales as $mg) {
                $destinatarios[] = [
                    'id' => $mg->ID,
                    'nombre' => "Prof. {$mg->Apellido}, {$mg->Nombre}",
                    'tipo' => $mg->Materia . ' (Grupal)',
                ];
            }
        }

        // 4. Preceptores del Curso
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
                        'tipo' => 'PRECEPTOR/A',
                    ];
                }
            }
        }

        // Retornamos coleccion filtrando profes duplicados (ej: Si enseña 2 materias al mismo alumno)
        return collect($destinatarios)->unique('id')->values()->all();
    }

    public function getChatDetails($codigo, $studentId, $familiaId)
    {
        // 1. Marcar como leído
        ChatMessage::where('Codigo', $codigo)
            ->where('ID_Destinatario', $familiaId)
            ->where('Tipo_Destinatario', 2)
            ->where('Leido', 0)
            ->update([
                'Leido' => 1,
                'Fecha_Leido' => date('Y-m-d'),
                'Hora_Leido' => date('H:i:s'),
            ]);

        // 2. Extraer historial
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
                    'leido' => $msj->Leido
                ];
            });

        return [
            'codigo' => $codigo,
            'mensajes' => $mensajes,
        ];
    }

    /**
     * Unifica inicio de conversación y respuestas aplicando la lógica del legacy
     */
    public function sendMessage(array $data)
    {
        return DB::transaction(function () use ($data) {
            $studentId = $data['id_alumno'];
            $familiaId = $data['id_familia'];
            $destinatarioId = $data['id_destinatario'];
            $texto = trim($data['mensaje']);
            $codigo = isset($data['codigo']) ? $data['codigo'] : null;

            $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
            $fecha = date('Y-m-d');
            $hora = date('H:i:s');

            // 1. Manejo del código de conversación
            if (!$codigo) {
                $codigo = Str::random(10);
                ChatConversation::create([
                    'ID_Docente' => $destinatarioId,
                    'ID_Familia' => $familiaId,
                    'ID_Alumno'  => $studentId,
                    'Codigo'     => $codigo,
                    'Fecha'      => $fecha,
                    'Hora'       => $hora
                ]);
            } else {
                // Actualizamos la cabecera
                DB::table('chat_codigo_conversaciones')
                    ->where('Codigo', $codigo)
                    ->update(['Fecha' => $fecha, 'Hora' => $hora]);
            }

            // 2. Supervisión Mensajería (Columna P)
            $supervision = DB::table('nivel_parametros')
                ->where('ID_Nivel', $alumno->ID_Nivel)
                ->value('Supervision_Mensajeria');
            $publico = ($supervision == 1) ? 0 : 1;

            // 3. Reglas de duplicación para Preceptoría y Niveles 1 y 3
            $destinatariosIds = [$destinatarioId];
            $grupo = DB::table('chat_grupos')->where('ID', $destinatarioId)->first();

            if (($grupo && $grupo->Referencia == 'PR') || in_array($alumno->ID_Nivel, [1, 3])) {
                $curso = DB::table('cursos')->where('ID', $alumno->ID_Curso)->first();
                if ($curso) {
                    $preceptores = array_filter([$curso->ID_Preceptor, $curso->ID_Pareja, $curso->ID_Pareja2]);
                    $destinatariosIds = array_unique(array_merge($destinatariosIds, $preceptores));
                }
            }

            // 4. Inserción segura de los mensajes
            foreach ($destinatariosIds as $idDest) {
                if ($idDest <= 0) continue;

                ChatMessage::create([
                    'Fecha'             => $fecha,
                    'Hora'              => $hora,
                    'ID_Remitente'      => $familiaId,
                    'Tipo_Remitente'    => 2, // Familia
                    'ID_Destinatario'   => $idDest,
                    'Tipo_Destinatario' => 1, // Personal
                    'Mensaje'           => $texto,
                    'Codigo'            => $codigo,
                    'ID_Alumno'         => $studentId,
                    'ID_Nivel'          => $alumno->ID_Nivel,
                    'P'                 => $publico,
                    'Leido'             => 0
                ]);
            }

            return $codigo;
        });
    }
}