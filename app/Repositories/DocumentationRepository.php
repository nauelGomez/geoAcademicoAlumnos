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

        // LA CONSULTA OPTIMIZADA (Sin la columna fantasma "Mensaje")
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
                'ed.Descripcion' // <-- Sacamos ed.Mensaje de acá
            )
            ->orderByDesc('ed.ID')
            ->get()
            ->map(function ($doc) {
                // Formateamos la fecha
                $fecha = $doc->Fecha ? Carbon::parse($doc->Fecha)->format('d/m/Y') : 'S/F';
                
                // Limpiamos los textos como lo hacía el legacy
                $titulo = trim($doc->Titulo) ?: 'Sin Título';
                $mensaje = trim($doc->Descripcion) ?: 'S/D';

                return [
                    'id_envio' => $doc->ID_Envio,
                    'fecha'    => $fecha,
                    'titulo'   => $titulo,
                    'mensaje'  => $mensaje, // Reutilizamos la descripción como mensaje para el front
                    'leido'    => (bool) $doc->Leido
                ];
            });

        return [
            'perfil' => [
                'alumno' => $alumno->Nombre . ' ' . $alumno->Apellido,
                'ciclo'  => $ciclo ?? 'Sin ciclo vigente'
            ],
            'documentos' => $documentos
        ];
    }
}