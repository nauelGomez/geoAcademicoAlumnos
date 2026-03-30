<?php

namespace App\Repositories\AppFamilias;

use Illuminate\Support\Facades\DB;

class PostRepository
{
    public function getStudentPosts($studentId, $familyEmail)
    {
        $hoy = date('Y-m-d');
        
        $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
        if (!$alumno) return [];

        // 1. Buscar publicaciones activas por nivel y fecha
        $posts = DB::table('publicaciones as p')
            ->join('publicaciones_detalle as pd', 'p.ID', '=', 'pd.ID_Comunicado')
            ->where('p.Estado', 'P')
            ->where('p.ID_Nivel', $alumno->ID_Nivel)
            ->where('p.Desde', '<=', $hoy)
            ->where('p.Hasta', '>=', $hoy)
            ->where('pd.ID_Destinatario', $studentId)
            ->where('pd.MailD', $familyEmail)
            ->select('p.*', 'pd.Leido')
            ->orderBy('p.ID', 'desc')
            ->get();

        return $posts->map(function($post) {
            $titulo = $post->Titulo;
            $descripcion = $post->Descripcion;

            // Manejo de codificación según lógica legacy
            if ($post->Fecha <= '2022-05-15') {
                $titulo = trim(utf8_encode($titulo));
                $descripcion = trim(utf8_encode($descripcion));
            } elseif ($post->Fecha >= '2025-08-02') {
                $titulo = utf8_encode($titulo);
                $descripcion = utf8_encode($descripcion);
            }

            return [
                'id' => $post->ID,
                'fecha' => date('d/m/Y', strtotime($post->Fecha)),
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'leido' => (bool)$post->Leido,
                'imagen_url' => $post->Imagen ? "publicaciones/" . $post->Imagen : null
            ];
        });
    }

    public function getPostDetail($postId, $studentId, $familyEmail)
    {
        $hoy = date("Y-m-d");
        $hora = date("H:i:s");

        // 1. Marcar como leído
        DB::table('publicaciones_detalle')
            ->where('ID_Comunicado', $postId)
            ->where('ID_Destinatario', $studentId)
            ->where('MailD', $familyEmail)
            ->update([
                'Leido' => 1,
                'Fecha_Leido' => $hoy,
                'Hora_Leido' => $hora
            ]);

        // 2. Obtener datos de la publicación
        $post = DB::table('publicaciones')->where('ID', $postId)->first();
        if (!$post) return null;

        // 3. Obtener imágenes de la galería
        // Nota: El legacy tiene un posible typo entre 'pubicaciones_imagenes' y 'publicaciones_imagenes'
        // Usamos la forma correcta, pero Windsurf debe verificar el nombre real en la DB.
        $images = DB::table('publicaciones_imagenes')
            ->where('ID_Publicacion', $postId)
            ->orderBy('ID')
            ->pluck('Imagen');

        $institucion = DB::table('institucion')->first();
        $rutaBase = "https://geoeducacion.com.ar/{$institucion->Carpeta}/difusion/";

        // 4. Manejo de codificación
        $titulo = $post->Titulo;
        $descripcion = $post->Descripcion;
        if ($post->Fecha <= '2022-05-15' || $post->Fecha >= '2025-08-02') {
            $titulo = utf8_encode($titulo);
            $descripcion = utf8_encode($descripcion);
        }

        return [
            'id' => $post->ID,
            'fecha' => date('d/m/Y', strtotime($post->Fecha)),
            'titulo' => trim($titulo),
            'descripcion' => trim($descripcion),
            'imagenes' => $images->map(function($img) use ($rutaBase) {
                return $rutaBase . $img;
            })
        ];
    }
}
