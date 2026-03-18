<?php

namespace App\Repositories;

use App\Models\Alumno;
use App\Models\Publicacion;
use Carbon\Carbon;

class PostRepository
{
    public function getPublications($studentId)
    {
        $alumno = Alumno::findOrFail($studentId);
        $hoy = Carbon::now()->format('Y-m-d');

        $publicaciones = Publicacion::with(['detalles' => function ($q) use ($alumno) {
                $q->where('ID_Destinatario', $alumno->ID);
            }])
            ->where('Estado', 'P')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Desde', '<=', $hoy)
            ->where('Hasta', '>=', $hoy)
            ->whereHas('detalles', function ($q) use ($alumno) {
                $q->where('ID_Destinatario', $alumno->ID);
            })
            ->orderBy('ID', 'desc')
            ->get()
            ->map(function ($pub) {
                $detalle = $pub->detalles->first();
                $leido = $detalle ? ($detalle->Leido == 1) : false;

                $fechaStr = '';
                try {
                    $fechaStr = Carbon::parse($pub->Fecha)->format('d/m/Y');
                } catch (\Exception $e) {
                    $fechaStr = '';
                }

                return [
                    'id' => $pub->ID,
                    'fecha' => $fechaStr,
                    'titulo' => $pub->Titulo,
                    'descripcion' => $pub->Descripcion,
                    'leido' => $leido,
                ];
            });

        return $publicaciones;
    }
}
