<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class NewsRepository
{
    public function markNewsAsRead(int $studentId, int $newsId)
    {
        // Usamos la conexión 'tenant' que el Middleware ya configuró
        $connection = DB::connection('tenant');

        // Buscamos los detalles del muro que son para el alumno
        // Nota: Cambiamos ->get() por una consulta directa para evitar el error de array
        $details = $connection->table('tareas_materia_muro_detalle')
            ->where('ID_Muro', $newsId)
            ->where('Tipo_Usuario', 'D')
            ->where('B', 0)
            ->get();

        $markedCount = 0;

        foreach ($details as $detail) {
            // Verificamos si ya existe la lectura
            $exists = $connection->table('tareas_materia_muro_lecturas')
                ->where('ID_Muro_Detalle', $detail->ID)
                ->where('ID_Alumno', $studentId)
                ->exists(); // exists() devuelve true/false, mucho más limpio

            if (!$exists) {
                $connection->table('tareas_materia_muro_lecturas')->insert([
                    'ID_Muro_Detalle' => $detail->ID,
                    'ID_Alumno' => $studentId
                ]);
                $markedCount++;
            }
        }

        return $markedCount;
    }
}