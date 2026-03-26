<?php

namespace App\Repositories\AppFamilias;

use App\Models\Alumno;
use App\Models\Muro;
use App\Models\MuroDetalle;
use App\Models\MuroLectura;
use App\Models\Personal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FamilyWallRepository
{
    public function getWalls($studentId)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) return [];

        // 1. Obtener Ciclo Lectivo Vigente
        $ciclo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->first();
        
        $cicloId = $ciclo ? $ciclo->ID : 0;
        $ficl = $ciclo ? $ciclo->IPT : '2000-01-01';

        // 2. Muros del Curso (Normales)
        $murosCurso = DB::table('tareas_materia_muro as m')
            ->leftJoin('materias as mat', 'm.ID_Materia', '=', 'mat.ID')
            ->leftJoin('personal as p', 'm.ID_Usuario', '=', 'p.ID')
            ->where('m.B', 0)
            ->where('m.ID_Curso', $alumno->ID_Curso)
            ->where('m.Fecha', '>=', $ficl)
            ->select('m.*', 'mat.Materia as Materia_Nombre', 'p.Apellido', 'p.Nombre')
            ->get();

        // 3. Muros de Materias Grupales (Donde el alumno está inscripto)
        $murosGrupales = DB::table('tareas_materia_muro as m')
            ->join('materias_grupales as mg', 'm.ID_Materia', '=', 'mg.ID')
            ->join('grupos as g', 'mg.ID', '=', 'g.ID_Materia_Grupal')
            ->leftJoin('personal as p', 'm.ID_Usuario', '=', 'p.ID')
            ->where('m.B', 0)
            ->where('m.ID_Curso', 0)
            ->where('m.Tipo_Materia', 'g')
            ->where('m.Fecha', '>=', $ficl)
            ->where('g.ID_Alumno', $studentId)
            ->where('g.ID_Ciclo_Lectivo', $cicloId)
            ->select('m.*', 'mg.Materia as Materia_Nombre', 'p.Apellido', 'p.Nombre')
            ->get();

        // Combinar ambas listas
        $todosLosMuros = $murosCurso->merge($murosGrupales)->sortByDesc('ID');

        $resultado = [];
        foreach ($todosLosMuros as $muro) {
            // Contar novedades sin leer en este muro específico
            $sinLeer = $this->countUnreadWallMessages($muro->ID, $studentId);

            $resultado[] = [
                'id' => $muro->ID,
                'fecha' => date('d/m/Y', strtotime($muro->Fecha)),
                'materia' => $muro->Materia_Nombre ?? 'General',
                'titulo' => $muro->Titulo,
                'docente' => "{$muro->Apellido}, {$muro->Nombre}",
                'sin_leer' => $sinLeer > 0,
                'cantidad_sin_leer' => $sinLeer
            ];
        }

        return $resultado;
    }

    private function countUnreadWallMessages($muroId, $studentId)
    {
        // Busca en el detalle del muro y cruza con la tabla de lecturas del alumno
        return DB::table('tareas_materia_muro_detalle as d')
            ->leftJoin('tareas_materia_muro_lecturas as l', function($join) use ($studentId) {
                $join->on('d.ID', '=', 'l.ID_Muro_Detalle')
                     ->where('l.ID_Alumno', '=', (int)$studentId);
            })
            ->where('d.ID_Muro', $muroId)
            ->where('d.Tipo_Usuario', 'D') // Solo mensajes de docentes
            ->where('d.B', 0)
            ->whereNull('l.ID') // Que no tengan registro de lectura
            ->count();
    }

    /**
     * Obtener el detalle del muro con todas sus intervenciones.
     * Marca automáticamente los mensajes del docente como leídos.
     *
     * @param int $wallId ID del muro
     * @param int $studentId ID del alumno
     * @return array Datos del muro con intervenciones
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getWallDetails($wallId, $studentId)
    {
        $muro = Muro::with(['docente', 'detalles'])->where('B', 0)->findOrFail($wallId);
        
        // Marcar como leídos los mensajes del docente que el alumno no haya visto
        $this->markWallAsRead($wallId, $studentId);

        // Procesar intervenciones con datos del usuario
        $intervenciones = $muro->detalles->map(function($item) {
            // Determinar el usuario según el tipo
            if ($item->Tipo_Usuario === 'D') {
                $user = Personal::find($item->ID_Usuario);
                $perfil = $user ? $user->PIC : 'default.png';
                $nombre = $user ? "{$user->Apellido}, {$user->Nombre}" : 'Docente';
            } else {
                $user = Alumno::find($item->ID_Usuario);
                $perfil = $user ? $user->Perfil : 'default.png';
                $nombre = $user ? "{$user->Apellido}, {$user->Nombre}" : 'Alumno';
            }

            return [
                'id' => $item->ID,
                'mensaje' => $item->Mensaje,
                'fecha' => Carbon::parse($item->Fecha)->format('d/m/Y'),
                'hora' => $item->Hora,
                'usuario' => $nombre,
                'perfil_img' => $perfil,
                'tipo_usuario' => $item->Tipo_Usuario
            ];
        });

        return [
            'muro_id' => $muro->ID,
            'titulo' => $muro->Titulo,
            'docente' => $muro->docente ? "{$muro->docente->Apellido}, {$muro->docente->Nombre}" : 'N/A',
            'intervenciones' => $intervenciones
        ];
    }

    /**
     * Marcar todos los mensajes sin leer del docente como leídos por el alumno.
     *
     * @param int $wallId ID del muro
     * @param int $studentId ID del alumno
     */
    private function markWallAsRead($wallId, $studentId)
    {
        $now = Carbon::now();
        
        // Obtener IDs de mensajes del docente no leídos por el alumno
        $unreadIds = DB::table('tareas_materia_muro_detalle as d')
            ->leftJoin('tareas_materia_muro_lecturas as l', function($join) use ($studentId) {
                $join->on('d.ID', '=', 'l.ID_Muro_Detalle')
                     ->where('l.ID_Alumno', '=', (int)$studentId);
            })
            ->where('d.ID_Muro', $wallId)
            ->where('d.Tipo_Usuario', 'D')
            ->where('d.B', 0)
            ->whereNull('l.ID')
            ->pluck('d.ID');

        // Registrar la lectura para cada mensaje no leído
        foreach ($unreadIds as $id) {
            MuroLectura::create([
                'ID_Alumno' => $studentId,
                'ID_Muro_Detalle' => $id,
                'Fecha_Leido' => $now->format('Y-m-d'),
                'Hora_Leido' => $now->format('H:i:s')
            ]);
        }
    }

    /**
     * Guardar una nueva intervención del alumno en el muro.
     *
     * @param int $wallId ID del muro
     * @param int $studentId ID del alumno
     * @param string $message Contenido del mensaje
     * @return MuroDetalle El detalle creado
     */
    public function storeIntervention($wallId, $studentId, $message)
    {
        return MuroDetalle::create([
            'ID_Muro' => $wallId,
            'ID_Usuario' => $studentId,
            'Tipo_Usuario' => 'A',
            'Fecha' => Carbon::now()->format('Y-m-d'),
            'Hora' => Carbon::now()->format('H:i:s'),
            'Mensaje' => $message,
            'B' => 0
        ]);
    }
}
