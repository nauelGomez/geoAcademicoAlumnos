<?php
namespace App\Repositories;

use App\Models\VirtualTest;
use App\Models\CicloLectivo;
use App\Models\Materia;
use App\Models\MateriaGrupal;
use Illuminate\Support\Facades\DB;

class VirtualTestRepository
{
    public function getAvailableForAlumno($alumno)
    {
        // 1. Regla de Negocio: Solo situación 2 (Activo/Regular)
        if ($alumno->ID_Situacion !== 2) {
            return collect();
        }

        // 2. Obtener Ciclo Lectivo Vigente para el nivel del alumno
        $ciclo = CicloLectivo::where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->first();

        if (!$ciclo) return collect();

        // 3. Obtener el Grupo (Agrupación) del alumno para este ciclo
        $idGrupo = DB::table('agrupaciones_detalle')
            ->where('ID_Alumno', $alumno->ID)
            ->where('ID_Ciclo_Lectivo', $ciclo->ID)
            ->value('ID_Grupo');

        // 4. Query Base: Traemos los tests del ciclo y curso del alumno
        $tests = VirtualTest::with(['docente', 'resoluciones' => function($q) use ($alumno) {
                $q->where('ID_Alumno', $alumno->ID);
            }])
            ->where('Envio', 1)
            ->where('ID_Ciclo_Lectivo', $ciclo->ID)
            ->where('ID_Curso', $alumno->ID_Curso)
            ->orderBy('ID', 'desc')
            ->get();

        // 5. Filtrado manual por lógica compleja de "Habilitación"
        return $tests->filter(function($test) use ($alumno, $idGrupo) {
            return $this->checkHabilitacion($test, $alumno, $idGrupo);
        })->map(function($test) use ($alumno) {
            return $this->mapTestData($test, $alumno);
        });
    }

    private function checkHabilitacion($test, $alumno, $idGrupo)
    {
        // Si el test tiene destinatarios seleccionados (Dest_Sel = 1)
        if ($test->Dest_Sel == 1) {
            $existeEnvio = DB::table('tareas_test_envios')
                ->where('ID_Tarea', $test->ID)
                ->where('ID_Destinatario', $alumno->ID)
                ->exists();
            if (!$existeEnvio) return false;
        }

        // Si el test depende de una Clase Virtual
        if (!empty($test->ID_Clase)) {
            $clase = DB::table('clases_virtuales')
                ->where('ID', $test->ID_Clase)
                ->where('Estado', 1)
                ->first();
            
            if (!$clase) return false;

            // Verificar fecha de publicación (clase vs agrupación)
            $fechaPub = $this->getFechaPublicacionClase($test->ID_Clase, $clase->Fecha_Publicacion, $idGrupo);
            if (date('Y-m-d') < $fechaPub) return false;
        }

        return true;
    }

    private function getFechaPublicacionClase($idClase, $fechaDefault, $idGrupo)
    {
        if (!$idGrupo) return $fechaDefault;

        $pubEspecifica = DB::table('clases_virtuales_publicacion')
            ->where('ID_Clase', $idClase)
            ->where('ID_Agrupacion', $idGrupo)
            ->value('Fecha_Publicacion');

        return $pubEspecifica ?: $fechaDefault;
    }

    private function mapTestData($test, $alumno)
    {
        $resolucion = $test->resoluciones->first();
        
        // Lógica de Materia (Dual Table)
        $materiaNombre = 'N/A';
        if ($test->Tipo_Materia == 2) {
            $materiaNombre = MateriaGrupal::where('ID', $test->ID_Materia)->value('Materia');
        } else {
            $materiaNombre = Materia::where('ID', $test->ID_Materia)->value('Materia');
        }

        return [
            'id' => $test->ID,
            'fecha' => date('d/m/Y', strtotime($test->Fecha)),
            'materia' => $materiaNombre,
            'docente' => ($test->docente->Apellido ?? '') . ' - ' . ($test->docente->Nombre ?? ''),
            'titulo' => $test->Titulo,
            'disponibilidad' => "Desde el ".date('d/m/Y', strtotime($test->Desde))." a las {$test->DesdeH} hasta el ".date('d/m/Y', strtotime($test->Hasta))." a las {$test->HastaH}",
            'estado' => $this->calculateStatus($resolucion),
            'icono_status' => $this->getIconoStatus($resolucion),
            'vencido' => date('Y-m-d') > $test->Hasta
        ];
    }

    private function calculateStatus($resolucion) {
        if (!$resolucion) return 'Pendiente';
        return ($resolucion->Correcion == 1) ? 'Evaluado' : 'Pendiente de Evaluación';
    }

    private function getIconoStatus($resolucion) {
        if (!$resolucion) return 'fa-envelope-open-o';
        return ($resolucion->Correcion == 1) ? 'fa-check-square' : 'fa-check';
    }
}