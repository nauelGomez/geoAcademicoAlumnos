<?php

namespace App\Repositories\AppFamilias;

use App\Models\Alumno;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FamilyPostRepository
{
    /**
     * Obtiene el listado general de posts/comunicados (Difusiones) para el alumno.
     */
    public function getPosts($studentId)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) return [];

        $hoy = Carbon::now()->format('Y-m-d');

        $publicaciones = DB::table('publicaciones as p')
            ->join('publicaciones_detalle as pd', 'p.ID', '=', 'pd.ID_Comunicado')
            ->where('p.Estado', 'P')
            ->where('p.ID_Nivel', $alumno->ID_Nivel)
            ->where('p.Desde', '<=', $hoy)
            ->where('p.Hasta', '>=', $hoy)
            ->where('pd.ID_Destinatario', $studentId)
            // Nota: Se omite MailD de legacy ya que en la API el ID_Destinatario es suficiente para identificar al alumno.
            ->select('p.ID', 'p.Fecha', 'p.Titulo', 'p.Descripcion', 'pd.Leido')
            ->orderBy('p.ID', 'desc')
            ->get()
            ->map(function ($pub) {
                // Parche legacy: los textos anteriores a 2022-05-15 necesitan utf8_encode
                $necesitaEncode = $pub->Fecha <= '2022-05-15';
                
                return [
                    'id' => $pub->ID,
                    'code' => $pub->ID,
                    'fecha' => $pub->Fecha,
                    'fecha_fmt' => Carbon::parse($pub->Fecha)->format('d/m/Y'),
                    'titulo' => $necesitaEncode ? utf8_encode(trim($pub->Titulo)) : $pub->Titulo,
                    'descripcion' => $necesitaEncode ? utf8_encode(trim($pub->Descripcion)) : $pub->Descripcion,
                    'leido' => (bool) $pub->Leido,
                    'remitente' => 'Institucional', // Fijo según UI
                    'tipo' => 1 // Fijo Institucional
                ];
            });

        return $publicaciones;
    }

    /**
     * Obtiene el detalle de un post específico y lo marca como leído.
     */
    public function getPostDetails($postId, $studentId)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) return null;

        $hoy = Carbon::now()->format('Y-m-d');

        // Buscar la publicación y validar destinatario
        $pub = DB::table('publicaciones as p')
            ->join('publicaciones_detalle as pd', 'p.ID', '=', 'pd.ID_Comunicado')
            ->where('p.ID', $postId)
            ->where('pd.ID_Destinatario', $studentId)
            ->where('p.Estado', 'P')
            ->where('p.Hasta', '>=', $hoy)
            ->select('p.*', 'pd.Leido')
            ->first();

        if (!$pub) return null;

        // Marcar como leído si no lo estaba
        if ($pub->Leido == 0) {
            DB::table('publicaciones_detalle')
                ->where('ID_Comunicado', $postId)
                ->where('ID_Destinatario', $studentId)
                ->update([
                    'Leido' => 1,
                    'Fecha_Leido' => $hoy,
                    'Hora_Leido' => Carbon::now()->format('H:i:s')
                ]);
        }

        $necesitaEncode = $pub->Fecha <= '2022-05-15';

        // Estructura adaptada para el modal del frontend
        return [
            'id' => $pub->ID,
            'titulo' => $necesitaEncode ? utf8_encode(trim($pub->Titulo)) : $pub->Titulo,
            'descripcion' => $necesitaEncode ? utf8_encode(trim($pub->Descripcion)) : $pub->Descripcion,
            'fecha' => $pub->Fecha,
            'fecha_fmt' => Carbon::parse($pub->Fecha)->format('d/m/Y'),
            'remitente' => 'Institucional',
            // Si la tabla publicaciones manejaba adjuntos (como Link o Archivo), mapearlos acá:
            // 'adjunto' => $pub->Adjunto ?? null,
            // 'adjunto_url' => $pub->URL_Adjunto ?? null,
        ];
    }
}
