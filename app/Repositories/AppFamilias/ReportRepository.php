<?php

namespace App\Repositories\AppFamilias;

use App\Models\Alumno;
use Illuminate\Support\Facades\DB;

class ReportRepository
{
    /**
     * Obtiene los informes pedagógicos del alumno.
     *
     * @param int $studentId
     * @return array
     */
    public function getReports($studentId)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) {
            return [];
        }

        // WINDSURF: Acá irá la lógica real de base de datos para los informes pedagógicos legacy.
        // Por ahora devolvemos un array vacío para estabilizar el endpoint.
        $informes = []; 

        return $informes;
    }

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

    public function markAsReadAndGetUrl(array $data)
    {
        $id = $data['id'];
        $urlType = $data['url_type'];
        $code = $data['code'] ?? null;
        
        $content = [];
        $pdfUrl = null;
        $fechaAcceso = date('Y-m-d');
        $horaAcceso = date('H:i:s');

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            if ($urlType === 'ver_informecl') {
                // Informes Cualitativos
                $detalle = \Illuminate\Support\Facades\DB::table('informes_cualitativos_detalle as icd')
                    ->join('informes_cualitativos as ic', 'ic.ID', '=', 'icd.ID_Informe')
                    ->where('icd.ID', $id)
                    ->select('ic.ID as informe_id', 'ic.Titulo', 'ic.Mensaje', 'ic.Fecha', 'icd.Leido', 'icd.Informe')
                    ->first();
                
                if ($detalle) {
                    // Obtener carpeta de la institución
                    $institucion = \Illuminate\Support\Facades\DB::table('institucion')
                        ->select('Carpeta')
                        ->first();
                    
                    $carpeta = $institucion ? $institucion->Carpeta : '';
                    
                    // Construir URL del PDF
                    if ($detalle->Informe) {
                        $pdfUrl = config('app.url') . '/' . $carpeta . '/informes_protegido/' . $detalle->Informe;
                    }
                    
                    // Marcar como leído + registrar fecha y hora
                    \Illuminate\Support\Facades\DB::table('informes_cualitativos_detalle')
                        ->where('ID', $id)
                        ->update([
                            'Leido' => 1,
                            'Fecha_Leido' => $fechaAcceso,
                            'Hora_Leido' => $horaAcceso
                        ]); 
                    
                    $content = [
                        'titulo'   => $detalle->Titulo,
                        'mensaje'  => $detalle->Mensaje,
                        'fecha'    => $detalle->Fecha,
                        'tipo'     => 'Informe Cualitativo',
                        'archivo'  => $detalle->Informe,
                        'leido_en' => $fechaAcceso . ' ' . $horaAcceso
                    ];
                }
                
            } else if ($urlType === 'ver_informecual') {
                // Evaluaciones Cualitativas
                $informe = \Illuminate\Support\Facades\DB::table('evaluaciones_cualitativas')
                    ->where('ID', $id)
                    ->select('ID', 'Titulo', 'Fecha', 'Archivo')
                    ->first();
                
                if ($informe) {
                    // Obtener carpeta de institución
                    $institucion = \Illuminate\Support\Facades\DB::table('institucion')
                        ->select('Carpeta')
                        ->first();
                    
                    $carpeta = $institucion ? $institucion->Carpeta : '';
                    
                    // Construir URL del PDF
                    if ($informe->Archivo) {
                        $pdfUrl = config('app.url') . '/' . $carpeta . '/informes_protegido/' . $informe->Archivo;
                    }
                    
                    // Marcar como leído en boletines_detalle
                    \Illuminate\Support\Facades\DB::table('boletines_detalle')
                        ->where('ID_Boletin', $id)
                        ->where('Tipo_Envio', 3)
                        ->update(['Leido' => 1]); 
                    
                    $content = [
                        'titulo'   => $informe->Titulo,
                        'fecha'    => $informe->Fecha,
                        'tipo'     => 'Evaluación Cualitativa',
                        'archivo'  => $informe->Archivo,
                        'leido_en' => $fechaAcceso . ' ' . $horaAcceso
                    ];
                }
                
            } else {
                // Otros tipos (boletines, IMT, etc)
                if ($code) {
                    // Boletín con código
                    $boletin = \Illuminate\Support\Facades\DB::table('boletines_detalle')
                        ->where('Aleatorio', $code)
                        ->first();
                    
                    \Illuminate\Support\Facades\DB::table('boletines_detalle')
                        ->where('Aleatorio', $code)
                        ->update(['Leido' => 1]); 
                    
                    if ($boletin) {
                        $content = [
                            'id'   => $boletin->ID_Boletin,
                            'code' => $code,
                            'tipo_envio' => $boletin->Tipo_Envio,
                            'leido_en' => $fechaAcceso . ' ' . $horaAcceso
                        ];
                    }
                } else {
                    // Otros con ID
                    $content = [
                        'id'   => $id,
                        'tipo' => $urlType,
                        'leido_en' => $fechaAcceso . ' ' . $horaAcceso
                    ];
                }
            }
            
            \Illuminate\Support\Facades\DB::commit();

            return [
                'file_url' => $pdfUrl,
                'code'     => $code,
                'data'     => $content
            ];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            throw $e;
        }
    }

    /**
     * Marca el informe cualitativo como leído y devuelve la URL o path del PDF
     */
    public function getInformeCualitativoPdf($idEnvio)
    {
        $informe = \Illuminate\Support\Facades\DB::table('informes_cualitativos_detalle')
            ->where('ID', $idEnvio)
            ->first();

        if (!$informe || empty($informe->Informe)) {
            throw new \Exception("El informe solicitado no existe o no tiene un archivo adjunto.");
        }

        $institucion = \Illuminate\Support\Facades\DB::table('institucion')->first();
        if (!$institucion) {
            throw new \Exception("Error de configuración de la institución.");
        }

        // Marcar como leído con fecha y hora actual
        $fechaAcceso = date('Y-m-d');
        $horaAcceso = date('H:i:s');
        
        \Illuminate\Support\Facades\DB::table('informes_cualitativos_detalle')
            ->where('ID', $idEnvio)
            ->update([
                'Leido'       => 1,
                'Fecha_Leido' => $fechaAcceso,
                'Hora_Leido'  => $horaAcceso
            ]);

        // Retornamos la URL absoluta tal como la armaba el sistema legacy
        // Si tu API está en el mismo server físico, sería mejor usar public_path() en lugar de la URL
        return "https://escuelaencasa.com.ar/{$institucion->Carpeta}/informes_protegido/{$informe->Informe}";
    }
}
