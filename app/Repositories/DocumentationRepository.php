<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DocumentationRepository
{
    public function getDocumentation($studentId, $email = '')
    {
        $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
        
        if (!$alumno) {
            return null;
        }

        $ciclo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->value('Ciclo_lectivo');

        // LA CONSULTA OPTIMIZADA: Join directo entre documento y el detalle de envío
        $documentos = DB::table('envio_documentacion_detalle as edd')
            ->join('envio_documentacion as ed', 'edd.ID_Doc', '=', 'ed.ID')
            ->where('edd.ID_Destinatario', $studentId)
            ->when($email, function($query) use ($email) {
                return $query->where('edd.MailD', $email);
            })
            ->where('ed.Visible', 1)
            ->where('ed.ID_Tipo', '<>', 1)
            ->where('ed.ID_Nivel', $alumno->ID_Nivel)
            ->select(
                'edd.ID as ID_Envio',
                'edd.Leido',
                'ed.Fecha',
                'ed.Titulo',
                'ed.Descripcion',
                'ed.Mensaje'
            )
            ->orderByDesc('ed.ID')
            ->get()
            ->map(function ($doc) {
                // Formateamos la fecha con Carbon
                $fecha = $doc->Fecha ? Carbon::parse($doc->Fecha)->format('d/m/Y') : 'S/F';
                
                // En el legacy había un cruce raro entre Titulo, Descripcion y Mensaje según un IF.
                // Limpiamos eso priorizando campos que no estén vacíos.
                $titulo = trim($doc->Titulo) ?: (trim($doc->Descripcion) ?: 'Sin Título');
                $mensaje = trim($doc->Mensaje) ?: (trim($doc->Descripcion) ?: 'S/D');

                return [
                    'id_envio' => $doc->ID_Envio,
                    'fecha'    => $fecha,
                    'titulo'   => $titulo,
                    'mensaje'  => $mensaje,
                    'leido'    => (bool) $doc->Leido
                ];
            });

        return [
            'alumno'     => $alumno->Nombre . ' ' . $alumno->Apellido,
            'ciclo'      => $ciclo ?? 'Sin ciclo vigente',
            'documentos' => $documentos
        ];
    }
}