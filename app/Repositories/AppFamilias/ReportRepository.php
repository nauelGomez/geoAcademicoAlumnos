<?php

namespace App\Repositories\AppFamilias;

use Illuminate\Support\Facades\DB;

class ReportRepository
{
    public function getStudentReports($studentId, $familyEmail)
    {
        $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
        if (!$alumno) return [];

        $ciclo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->first();
        $cicloId = $ciclo ? $ciclo->ID : 0;

        $allReports = [];

        // 1. Informes Cualitativos
        $infCualitativos = DB::table('informes_cualitativos as ic')
            ->join('informes_cualitativos_detalle as icd', 'ic.ID', '=', 'icd.ID_Informe')
            ->where('ic.Visible', 1)
            ->where('icd.ID_Destinatario', $studentId)
            ->where(function($q) use ($alumno) {
                $q->where('ic.ID_Nivel', $alumno->ID_Nivel)->orWhere('ic.ID_Nivel', 0);
            })
            ->select('ic.ID', 'ic.Fecha', 'ic.Titulo', 'ic.Mensaje', 'icd.Leido', 'icd.ID as Envio_ID')
            ->get();

        foreach ($infCualitativos as $r) {
            $allReports[] = [
                'id' => $r->Envio_ID,
                'fecha' => date('d/m/Y', strtotime($r->Fecha)),
                'fecha_raw' => $r->Fecha,
                'titulo' => $r->Titulo . ($r->Mensaje != ' ' ? ". {$r->Mensaje}" : ""),
                'tipo' => 'Informe Cualitativo',
                'leido' => (bool)$r->Leido,
                'url_type' => 'ver_informecl'
            ];
        }

        // 2. Procesos de Valoración (Boletines)
        $procesos = DB::table('procesos_valoracion')
            ->where('ID_Ciclo_Lectivo', $cicloId)
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Envio', 1)
            ->where('ID_Estado', '<>', 3)
            ->get();

        foreach ($procesos as $p) {
            // Verificar si el alumno tiene datos en este proceso
            $tieneDatos = DB::table('procesos_valoracion_detalle_materias')
                ->where('ID_Proyecto', $p->ID)
                ->where('ID_Alumno', $studentId)
                ->exists();
            
            if (!$tieneDatos) continue;

            $tipoEnvio = ($p->IMT == 1) ? 7 : 2;
            $detalle = DB::table('boletines_detalle')
                ->where('ID_Destinatario', $studentId)
                ->where('ID_Boletin', $p->ID)
                ->where('Tipo_Envio', $tipoEnvio)
                ->first();

            $allReports[] = [
                'id' => $p->ID,
                'fecha' => date('d/m/Y', strtotime($p->Fecha)),
                'fecha_raw' => $p->Fecha,
                'titulo' => $p->Denominacion,
                'tipo' => 'Proceso de Valoración',
                'leido' => $detalle ? (bool)$detalle->Leido : false,
                'code' => $detalle ? $detalle->Aleatorio : null,
                'url_type' => ($p->IMT == 1) ? 'ver_imt' : (($p->Tipo == 2) ? 'api_valoracion' : 'ver_informepvpl')
            ];
        }

        // 3. RITE (Registro Institucional de Trayectoria Educativa)
        $rites = DB::table('rite')
            ->where('ID_Ciclo_Lectivo', $cicloId)
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Envio', 1)
            ->get();

        foreach ($rites as $ri) {
            $detalle = DB::table('boletines_detalle')
                ->where('ID_Destinatario', $studentId)
                ->where('ID_Boletin', $ri->ID)
                ->where('Tipo_Envio', 8)
                ->first();

            if ($detalle) {
                $allReports[] = [
                    'id' => $ri->ID,
                    'fecha' => date('d/m/Y', strtotime($ri->Fecha)),
                    'fecha_raw' => $ri->Fecha,
                    'titulo' => $ri->Titulo,
                    'tipo' => 'RITE',
                    'leido' => (bool)$detalle->Leido,
                    'code' => $detalle->Aleatorio,
                    'url_type' => 'ver_imt'
                ];
            }
        }

        // 4. Evaluaciones Cualitativas
        $evalCual = DB::table('evaluaciones_cualitativas')
            ->where('ID_Curso', $alumno->ID_Curso)
            ->where('Envio', 1)
            ->where('ID_Ciclo_Lectivo', $cicloId)
            ->get();

        foreach ($evalCual as $ec) {
            $leido = DB::table('boletines_detalle')
                ->where('ID_Destinatario', $studentId)
                ->where('ID_Boletin', $ec->ID)
                ->where('Tipo_Envio', 3)
                ->value('Leido');

            $allReports[] = [
                'id' => $ec->ID,
                'fecha' => date('d/m/Y', strtotime($ec->Fecha)),
                'fecha_raw' => $ec->Fecha,
                'titulo' => $ec->Titulo,
                'tipo' => 'Evaluación Cualitativa',
                'leido' => (bool)$leido,
                'url_type' => 'ver_informecual'
            ];
        }

        // Ordenar todos los informes por fecha descendente
        usort($allReports, function($a, $b) {
            return strcmp($b['fecha_raw'], $a['fecha_raw']);
        });

        return $allReports;
    }
}
