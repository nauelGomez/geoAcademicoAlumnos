<?php

namespace App\Repositories;

use App\Models\Alumno;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PostRepository
{
    /**
     * Listado general de comunicados (Tipo 1) y notificaciones (Tipo 2)
     */
    public function getPublications($studentId)
    {
        $alumno = Alumno::findOrFail($studentId);

        // 1. Comunicados (Tipo 1)
        $comunicados = DB::table('comunicados_detalle as cd')
            ->join('comunicados as c', 'cd.ID_Comunicado', '=', 'c.ID')
            ->where('cd.ID_Destinatario', $studentId)
            ->select('cd.ID as ID_Detalle', 'cd.Aleatorio', 'cd.Leido', 'c.ID as ID_Com', 'c.Fecha', 'c.Titulo', 'c.Tipo', 'c.ID_Autor', 'c.ID_Materia', 'c.ID_Curso')
            ->orderBy('cd.ID', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->Aleatorio, // Usamos el Aleatorio como ID unificado para el frontend
                    'code' => $item->Aleatorio,
                    'fecha' => $item->Fecha,
                    'fecha_fmt' => Carbon::parse($item->Fecha)->format('d/m/Y'),
                    'titulo' => utf8_encode($item->Titulo),
                    'remitente' => $this->resolveRemitente($item->Tipo, $item->ID_Autor, $item->ID_Materia, $item->ID_Curso),
                    'leido' => (bool) $item->Leido,
                    'tipo' => 1
                ];
            });

        // 2. Notificaciones Enviadas (Tipo 2)
        $notificaciones = DB::table('notificaciones_enviadas as ne')
            ->leftJoin('personal as p', 'ne.ID_Personal', '=', 'p.ID')
            ->where('ne.ID_Alumno', $studentId)
            ->whereNotNull('ne.Aleatorio')
            ->where('ne.Aleatorio', '!=', '')
            ->select('ne.ID', 'ne.Aleatorio', 'ne.Leido', 'ne.Fecha', 'ne.Titulo', 'p.Apellido', 'p.Nombre')
            ->orderBy('ne.ID', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->Aleatorio,
                    'code' => $item->Aleatorio,
                    'fecha' => $item->Fecha,
                    'fecha_fmt' => Carbon::parse($item->Fecha)->format('d/m/Y'),
                    'titulo' => utf8_encode($item->Titulo),
                    'remitente' => utf8_encode("Prof. {$item->Apellido}"),
                    'leido' => ($item->Leido === 'SI' || $item->Leido == 1),
                    'tipo' => 2
                ];
            });

        // Combinar ambas colecciones, ordenar por fecha descendente y resetear índices
        $todas = $comunicados->merge($notificaciones)
                             ->sortByDesc('fecha')
                             ->values()
                             ->toArray();

        return $todas;
    }

    /**
     * Obtiene el detalle buscando por el código Aleatorio y lo marca como leído.
     */
    public function getPublicationDetails($postId, $studentId)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) return null;

        $hoy = Carbon::now()->format('Y-m-d');
        $hora = Carbon::now()->format('H:i:s');

        // INTENTO 1: Buscar si es un Comunicado (Tipo 1)
        $detalleCom = DB::table('comunicados_detalle')
            ->where('Aleatorio', $postId)
            ->where('ID_Destinatario', $studentId)
            ->first();

        if ($detalleCom) {
            // Marcar como leído
            if ($detalleCom->Leido == 0) {
                DB::table('comunicados_detalle')
                    ->where('ID', $detalleCom->ID)
                    ->update([
                        'Leido' => 1,
                        'Fecha_Leido' => $hoy,
                        'Hora_Leido' => $hora,
                        'Envio' => 1
                    ]);
            }

            $c = DB::table('comunicados')->where('ID', $detalleCom->ID_Comunicado)->first();
            if (!$c) return null;

            return [
                'id' => $postId,
                'titulo' => utf8_encode($c->Titulo),
                'descripcion' => utf8_encode($c->Descripcion),
                'fecha' => $c->Fecha,
                'fecha_fmt' => Carbon::parse($c->Fecha)->format('d/m/Y'),
                'remitente' => $this->resolveRemitente($c->Tipo, $c->ID_Autor, $c->ID_Materia, $c->ID_Curso),
                'adjunto' => $c->Adjunto ? 'Archivo Adjunto' : null,
                'adjunto_url' => $c->Adjunto ? url('/institucion/comunicados/' . $c->Adjunto) : null,
            ];
        }

        // INTENTO 2: Buscar si es una Notificación (Tipo 2)
        $noti = DB::table('notificaciones_enviadas')
            ->where('Aleatorio', $postId)
            ->where('ID_Alumno', $studentId)
            ->first();

        if ($noti) {
            // Marcar como leído
            if ($noti->Leido == 'NO' || $noti->Leido == 0) {
                DB::table('notificaciones_enviadas')
                    ->where('ID', $noti->ID)
                    ->update([
                        'Leido' => 1, // En legacy usaban 'SI' y 'NO', forzamos a 1
                        'Fecha_Leido' => $hoy,
                        'Hora_Leido' => $hora,
                        'Enviada' => 1
                    ]);
            }

            $p = DB::table('personal')->where('ID', $noti->ID_Personal)->first();
            $remitente = $p ? utf8_encode("Prof. {$p->Apellido}") : 'Profesor';

            return [
                'id' => $postId,
                'titulo' => utf8_encode($noti->Titulo),
                'descripcion' => utf8_encode($noti->Mensaje),
                'fecha' => $noti->Fecha,
                'fecha_fmt' => Carbon::parse($noti->Fecha)->format('d/m/Y'),
                'remitente' => $remitente,
                'adjunto' => $noti->Adjunto ? 'Archivo Adjunto' : null,
                'adjunto_url' => $noti->Adjunto ? url('/institucion/comunicados/' . $noti->Adjunto) : null,
            ];
        }

        return null; // Si no lo encontró en ninguna de las dos tablas
    }

    /**
     * Marca una publicación (Comunicado o Notificación) como leída explícitamente.
     */
    public function markAsRead($postId, $studentId)
    {
        $hoy = Carbon::now()->format('Y-m-d');
        $hora = Carbon::now()->format('H:i:s');

        // Intento 1: Actualizar en Comunicados
        $updated = DB::table('comunicados_detalle')
            ->where('Aleatorio', $postId)
            ->where('ID_Destinatario', $studentId)
            ->where('Leido', 0)
            ->update([
                'Leido' => 1,
                'Fecha_Leido' => $hoy,
                'Hora_Leido' => $hora,
                'Envio' => 1
            ]);

        if ($updated) return true;

        // Intento 2: Actualizar en Notificaciones (si no era comunicado)
        $updatedNoti = DB::table('notificaciones_enviadas')
            ->where('Aleatorio', $postId)
            ->where('ID_Alumno', $studentId)
            ->whereIn('Leido', ['NO', '0', 0]) // Legacy usaba 'NO' o 0
            ->update([
                'Leido' => 1,
                'Fecha_Leido' => $hoy,
                'Hora_Leido' => $hora,
                'Enviada' => 1
            ]);

        return $updatedNoti > 0;
    }

    /**
     * Resuelve el nombre del remitente aplicando la misma lógica del legacy.
     */
    private function resolveRemitente($tipo, $idAutor, $idMateria, $idCurso)
    {
        if ($tipo == 'I') {
            return 'Institucional';
        }

        $autor = DB::table('personal')->where('ID', $idAutor)->first();
        $apellidoAutor = $autor ? $autor->Apellido : 'Desconocido';

        if ($idMateria >= 1) {
            $materia = DB::table('materias')->where('ID', $idMateria)->first();
            $nombreMateria = $materia ? $materia->Materia : 'Materia';
            return utf8_encode("Prof. {$apellidoAutor} ({$nombreMateria})");
        } else {
            $curso = DB::table('cursos')->where('ID', $idCurso)->first();
            $nombreCurso = $curso ? $curso->Cursos : 'Curso';
            return utf8_encode("Prof. {$apellidoAutor} ({$nombreCurso})");
        }
    }
}