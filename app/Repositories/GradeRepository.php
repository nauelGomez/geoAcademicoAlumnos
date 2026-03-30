<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Exception;

class GradeRepository
{
    /**
     * Obtiene la evolución de cursada de un alumno formateada para el frontend
     *
     * @param int|string $alumnoId // <--- Sacamos el tipado estricto
     * @return array
     * @throws Exception
     */
    public function getEvolucionCursada($alumnoId): array // <--- Firma limpia (sin connectionName y sin int)
    {
        // 1. Datos del Alumno y su Carrera usando DB Facade directo
        $alumno = DB::table('alumnos')
            ->join('cursos', 'alumnos.ID_Curso', '=', 'cursos.ID')
            ->join('planes_estudio', 'cursos.ID_Plan', '=', 'planes_estudio.ID')
            ->where('alumnos.ID', $alumnoId)
            ->select(
                'alumnos.Nombre', 
                'alumnos.Apellido', 
                'cursos.ID_Plan', 
                'planes_estudio.Nombre as Carrera'
            )
            ->first();

        if (!$alumno) {
            throw new Exception("Alumno no encontrado o sin plan de estudio asignado.");
        }

        // 2. Obtener la estructura del Plan (Años/Cursos: 1ro, 2do, 3ro...)
        $anios = DB::table('planes_estudio_cursos')
            ->where('ID_Plan', $alumno->ID_Plan)
            ->orderBy('Orden')
            ->get();

        // 3. Obtener todas las materias del plan
        $materiasPlanes = DB::table('materias_planes')
            ->where('ID_Plan', $alumno->ID_Plan)
            ->orderBy('Orden')
            ->get();

        $materiasPlanesIds = $materiasPlanes->pluck('ID')->toArray();

        // Evitar error si no hay materias en el plan
        if (empty($materiasPlanesIds)) {
             throw new Exception("El plan de estudios no tiene materias asignadas.");
        }

        $materiasReales = DB::table('materias')
            ->whereIn('ID_Materia_Plan', $materiasPlanesIds)
            ->get();
            
        $materiasRealesIds = $materiasReales->pluck('ID')->toArray();

        $notasCursada = collect(); 

        // 4. Obtener TODAS las notas del alumno 
        if (!empty($materiasRealesIds)) {
             $notasCursada = DB::table('notas_cursada')
                ->where('ID_Alumno', $alumnoId)
                ->whereIn('ID_Materia', $materiasRealesIds)
                ->get()
                ->keyBy('ID_Materia');
        }

        $materiasRealesAgrupadas = $materiasReales->groupBy('ID_Materia_Plan');
        $evolucion = [];

        // 5. Ensamblaje de datos en memoria
        foreach ($anios as $anio) {
            $materiasDelAnio = $materiasPlanes->where('Curso', $anio->ID);
            $listadoMaterias = [];

            foreach ($materiasDelAnio as $materiaPlan) {
                $cursadaStatus = 'Pendiente';
                $finalStatus = 'Pendiente';
                $observaciones = '';

                // Buscar nota entre las instancias reales de esta materia del plan
                $materiasAsociadas = $materiasRealesAgrupadas->get($materiaPlan->ID, collect([]));
                $nota = null;

                foreach ($materiasAsociadas as $matReal) {
                    if ($notasCursada->has($matReal->ID)) {
                        $nota = $notasCursada->get($matReal->ID);
                        break;
                    }
                }

                // Formateo visual
                if ($nota) {
                    $observaciones = $nota->Observaciones;
                    $fechaCursada = $nota->Fecha_Cursada ? date('d/m/Y', strtotime($nota->Fecha_Cursada)) : '';
                    
                    if ($nota->Cursada == 1) {
                        $estado = ($nota->Promocion == 1) ? 'Promocionada' : 'Aprobada';
                        $cursadaStatus = "{$nota->Calificacion_Cursada} {$estado} ({$fechaCursada})";
                    } else {
                        $cursadaStatus = "{$nota->Calificacion_Cursada} Desaprobada ({$fechaCursada})";
                    }

                    if (strtoupper($nota->Final) === 'SI') {
                        $fechaFinal = $nota->Fecha ? date('d/m/Y', strtotime($nota->Fecha)) : '';
                        $finalStatus = "{$nota->Calificacion} ({$fechaFinal})";
                    }
                }

                $listadoMaterias[] = [
                    'materia' => $materiaPlan->Materia, 
                    'cursada' => $cursadaStatus,
                    'final' => $finalStatus,
                    'observaciones' => $observaciones
                ];
            }

            $evolucion[] = [
                'anio_nombre' => $anio->Curso, 
                'materias' => $listadoMaterias
            ];
        }

        return [
            'alumno' => trim("{$alumno->Nombre} {$alumno->Apellido}"),
            'carrera' => $alumno->Carrera,
            'periodos' => $evolucion
        ];
    }
}