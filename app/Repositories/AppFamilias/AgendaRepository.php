<?php

namespace App\Repositories\AppFamilias;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AgendaRepository
{
    public function getStudentAgenda($studentId)
    {
        $hoy = date('Y-m-d');
        
        // 1. Datos básicos del alumno
        $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
        if (!$alumno) return [];

        // 2. Ciclo lectivo y Agrupación (Burbuja)
        $ciclo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->first();
        $cicloId = $ciclo ? $ciclo->ID : 0;

        $agrupacion = DB::table('agrupaciones_detalle as ad')
            ->join('agrupaciones as a', 'ad.ID_Grupo', '=', 'a.ID')
            ->where('ad.ID_Alumno', $studentId)
            ->where('a.ID_Ciclo_Lectivo', $cicloId)
            ->select('a.ID', 'a.Grupo')
            ->first();
        $idAgrupacionAlumno = $agrupacion ? $agrupacion->ID : null;

        // 3. Consulta principal de la agenda
        $eventosRaw = DB::table('agenda_comun as a')
            ->where('a.Fecha_R', '>=', $hoy)
            ->where('a.B', 0)
            ->orderBy('a.Fecha_R', 'asc')
            ->orderBy('a.Hora_Inicio', 'asc')
            ->get();

        $agendaProcesada = [];

        foreach ($eventosRaw as $evento) {
            $permitido = false;
            $leyendaBurbuja = '';

            // --- Lógica de Filtrado por Curso/Grupo ---
            $gruposEvento = DB::table('agenda_comun_grupos')->where('ID_Evento', $evento->ID)->pluck('ID_Grupo')->toArray();

            if (empty($gruposEvento)) {
                // Si no hay grupos específicos, es para todo el curso o institucional
                if ($evento->ID_Curso == $alumno->ID_Curso || $evento->ID_Curso == 0) {
                    $permitido = true;
                }
            } else {
                // Si hay grupos asignados, el alumno debe estar en uno de ellos
                if ($idAgrupacionAlumno && in_array($idAgrupacionAlumno, $gruposEvento)) {
                    $permitido = true;
                    $leyendaBurbuja = $agrupacion->Grupo;
                }
            }

            if (!$permitido) continue;

            // --- Información de Materia y Docente ---
            $materiaNombre = 'Institucional';
            $docenteNombre = '';
            
            if ($evento->ID_Materia > 0) {
                if ($evento->ID_Materia > 1000) { // Materia Grupal
                    $idMatReal = substr($evento->ID_Materia, 4, 8);
                    $matInfo = DB::table('materias_grupales')->where('ID', $idMatReal)->first();
                    $materiaNombre = $matInfo->Materia ?? 'Materia';
                    $idProfe = $matInfo->ID_Personal ?? $evento->ID_Docente;
                } else { // Materia Normal
                    $matInfo = DB::table('materias')->where('ID', $evento->ID)->first();
                    $materiaNombre = $matInfo->Materia ?? 'Materia';
                    $idProfe = $matInfo->ID_Personal ?? $evento->ID_Docente;
                }
                
                $profe = DB::table('personal')->where('ID', $idProfe)->first();
                $docenteNombre = $profe ? "Prof. {$profe->Apellido}" : "";
            }

            // --- Código de Acceso Personal ---
            $cp = DB::table('agenda_comun_cp')
                ->where('ID_Alumno', $studentId)
                ->where('ID_Evento', $evento->ID)
                ->value('Aleatorio');

            // --- Formateo de Icono/Tipo ---
            $tipoLabel = $this->getTipoLabel($evento->ID_Categoria, $evento->ID_Tipo);
            $logo = $this->getLogo($evento->ID_Tipo, $evento->Campo_1);

            $fechaObj = Carbon::parse($evento->Fecha_R);

            $agendaProcesada[] = [
                'id' => $evento->ID,
                'fecha_raw' => $evento->Fecha_R,
                'dia' => $fechaObj->format('d'),
                'mes' => strtoupper($fechaObj->formatLocalized('%b')),
                'hora_inicio' => date('H:i', strtotime($evento->Hora_Inicio)),
                'hora_fin' => date('H:i', strtotime($evento->Hora_Fin)),
                'categoria' => ($evento->ID_Categoria == 1) ? 'Institucional' : 'Curso',
                'tipo' => $tipoLabel,
                'materia' => $materiaNombre,
                'docente' => $docenteNombre,
                'burbuja' => $leyendaBurbuja,
                'descripcion' => $evento->Campo_2,
                'actividad' => $evento->Actividad,
                'link' => ($evento->ID_Tipo == 1) ? $evento->Campo_3 : null,
                'codigo_personal' => $cp,
                'logo' => $logo
            ];
        }

        return $agendaProcesada;
    }

    private function getTipoLabel($cat, $tipo) {
        if ($tipo == 1) return 'Encuentro Virtual';
        if ($tipo == 2) return 'Encuentro Presencial';
        if ($tipo == 3) return 'Entrega de Tarea';
        return 'Evento';
    }

    private function getLogo($tipo, $plataforma) {
        if ($tipo == 1) {
            return (strtolower($plataforma) == 'zoom') ? 'logo_zoom.jpg' : 'logo_meet.jpg';
        }
        if ($tipo == 2) return 'escuela_icono.jpg';
        if ($tipo == 3) return 'tarea_icono.png';
        return 'default.jpg';
    }
}
