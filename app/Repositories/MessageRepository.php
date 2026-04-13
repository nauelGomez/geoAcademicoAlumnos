<?php
namespace App\Repositories;

use App\Models\Alumno;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MessageRepository
{
    /**
     * Obtener destinatarios disponibles respetando materias grupales y preceptores
     */
    public function getAvailableRecipients($studentId)
    {
        $alumno = Alumno::findOrFail($studentId);
        $recipients = collect();

        // 1. Grupos Institucionales (DI, PR, EQ)
        $grupos = DB::table('chat_grupos')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->whereIn('Referencia', ['DI', 'PR', 'EQ'])
            ->get(['ID', 'Nombre', 'Referencia']);

        foreach ($grupos as $g) {
            $recipients->push(['id' => $g->ID, 'nombre' => $g->Nombre, 'tipo' => 'GRUPO']);
        }

        // 2. Docentes de Materias Normales (Titulares y Adjuntos)
        $docentesMat = DB::table('materias as m')
            ->join('personal as p', function($join) {
                $join->on('m.ID_Personal', '=', 'p.ID')
                     ->orOn('m.ID_Adjunto', '=', 'p.ID');
            })
            ->where('m.ID_Curso', $alumno->ID_Curso)
            ->where('m.Mensajeria', 1)
            ->where('p.Estado', 'H')
            ->select('p.ID', 'p.Apellido', 'p.Nombre', 'm.Materia')
            ->get();

        foreach ($docentesMat as $d) {
            $recipients->push([
                'id' => $d->ID, 
                'nombre' => "Prof. {$d->Apellido}, {$d->Nombre} ({$d->Materia})", 
                'tipo' => 'DOCENTE'
            ]);
        }

        // 3. Materias Grupales (Lógica Legacy líneas 173-201)
        $docentesGrupales = DB::table('materias_grupales as mg')
            ->join('grupos as g', 'g.ID_Materia_Grupal', '=', 'mg.ID')
            ->join('personal as p', function($join) {
                $join->on('mg.ID_Personal', '=', 'p.ID')
                     ->orOn('mg.ID_Adjunto', '=', 'p.ID');
            })
            ->where('g.ID_Alumno', $studentId)
            ->where('mg.Estado', 0)
            ->where('p.Estado', 'H')
            ->select('p.ID', 'p.Apellido', 'p.Nombre', 'mg.Materia')
            ->get();

        foreach ($docentesGrupales as $dg) {
            $recipients->push([
                'id' => $dg->ID, 
                'nombre' => "Prof. {$dg->Apellido}, {$dg->Nombre} ({$dg->Materia})", 
                'tipo' => 'DOCENTE'
            ]);
        }

        return $recipients->unique('id')->values();
    }

    /**
     * Enviar mensaje con lógica de duplicación para Niveles 1 y 3
     */
    public function sendMessage(array $data)
    {
        return DB::transaction(function () use ($data) {
            $alumno = Alumno::findOrFail($data['id_alumno']);
            $fecha = date('Y-m-d');
            $hora = date('H:i:s');

            // Determinar Supervisión (Columna P)
            $supervision = DB::table('nivel_parametros')
                ->where('ID_Nivel', $alumno->ID_Nivel)
                ->value('Supervision_Mensajeria');
            $publico = ($supervision == 1) ? 0 : 1;

            // Obtener o Crear Conversación (Header)
            $conv = ChatConversation::firstOrCreate(
                [
                    'ID_Docente' => $data['id_destinatario'],
                    'ID_Familia' => $data['id_familia'],
                    'ID_Alumno'  => $data['id_alumno']
                ],
                [
                    'Codigo' => Str::random(10),
                    'Fecha'  => $fecha,
                    'Hora'   => $hora
                ]
            );

            // Actualizamos fecha de cabecera para que suba en la lista
            $conv->update(['Fecha' => $fecha, 'Hora' => $hora]);

            // Lógica de envío múltiple (Preceptores)
            $destinatarios = [$data['id_destinatario']];
            
            $grupo = DB::table('chat_grupos')->where('ID', $data['id_destinatario'])->first();
            
            // Si el destino es el grupo de Preceptoría (PR) o es Nivel 1/3 (Regla legacy 472)
            if (($grupo && $grupo->Referencia == 'PR') || in_array($alumno->ID_Nivel, [1, 3])) {
                $curso = DB::table('cursos')->where('ID', $alumno->ID_Curso)->first();
                if ($curso) {
                    $preceptores = array_filter([$curso->ID_Preceptor, $curso->ID_Pareja, $curso->ID_Pareja2]);
                    $destinatarios = array_unique(array_merge($destinatarios, $preceptores));
                }
            }

            foreach ($destinatarios as $destId) {
                ChatMessage::create([
                    'Fecha' => $fecha,
                    'Hora' => $hora,
                    'ID_Remitente' => $data['id_familia'],
                    'Tipo_Remitente' => 2, // Familia
                    'ID_Destinatario' => $destId,
                    'Tipo_Destinatario' => 1, // Personal
                    'Mensaje' => $data['mensaje'],
                    'Codigo' => $conv->Codigo,
                    'ID_Alumno' => $alumno->ID,
                    'ID_Nivel' => $alumno->ID_Nivel,
                    'P' => $publico,
                    'Leido' => 0
                ]);
            }

            return $conv->Codigo;
        });
    }
    /**
     * Obtener listado de conversaciones (Bandeja de entrada)
     */
    public function getConversations($studentId, $familiaId)
    {
        $conversaciones = ChatConversation::where('ID_Alumno', $studentId)
            ->where('ID_Familia', $familiaId)
            ->orderBy('Fecha', 'desc')
            ->orderBy('Hora', 'desc')
            ->get();

        return $conversaciones->map(function ($conv) use ($familiaId) {
            $ultimoMensaje = DB::table('chat')
                ->where('Codigo', $conv->Codigo)
                ->orderBy('ID', 'desc')
                ->first();

            if (!$ultimoMensaje) return null;

            // Contar no leídos para la familia (Tipo_Destinatario = 2)
            $sinLeer = DB::table('chat')
                ->where('Codigo', $conv->Codigo)
                ->where('ID_Destinatario', $familiaId)
                ->where('Tipo_Destinatario', 2)
                ->where('Leido', 0)
                ->count();

            // Resolver nombre del destinatario (Legacy chat_grupos o personal)
            $nombreDocente = 'Desconocido';
            $grupo = DB::table('chat_grupos')->where('ID', $conv->ID_Docente)->first();
            if ($grupo) {
                $nombreDocente = $grupo->Nombre;
            } else {
                $profe = DB::table('personal')->where('ID', $conv->ID_Docente)->first();
                if ($profe) $nombreDocente = "Prof. {$profe->Apellido}, {$profe->Nombre}";
            }

            return [
                'codigo' => $conv->Codigo,
                'docente' => $nombreDocente,
                'ultimo_mensaje' => $ultimoMensaje->Mensaje,
                'fecha_um' => date('d/m/Y', strtotime($ultimoMensaje->Fecha)),
                'hora_um' => $ultimoMensaje->Hora,
                'sin_leer' => $sinLeer
            ];
        })->filter()->values();
    }

/**
 * Busca el ID de familia asociado a un alumno en la DB General
 * validando que pertenezca a la institución activa.
 */
public function getFamilyIdFromAsociacion($studentId, $institutionId)
{
    return DB::connection('mysql_gral')
        ->table('asociaciones')
        ->where('ID_Alumno', $studentId)
        ->where('ID_Institucion', $institutionId) // Filtro crítico según tu imagen
        ->where('Estado', 1) // Opcional: solo asociaciones activas
        ->value('ID_Familia');
}
}