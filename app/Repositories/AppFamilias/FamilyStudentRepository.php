<?php

namespace App\Repositories\AppFamilias;

use App\Services\DatabaseManager;
use Illuminate\Support\Facades\DB;

class FamilyStudentRepository
{
    public function getLinkedStudents($familiaId, $institucionId)
    {
        // 1. Buscar asociaciones en la base de datos de familias usando mysql_gral
        $asociaciones = DB::connection('mysql_gral')
            ->table('asociaciones')
            ->where('ID_Familia', $familiaId)
            ->where('ID_Institucion', $institucionId)
            ->get();
        
        $estudiantes = [];
        
        foreach ($asociaciones as $asoc) {
            // 2. Buscar el alumno en la base de datos de la institución usando el ID_Alumno
            $alumnosTable = DatabaseManager::table('alumnos', $institucionId);
            $alumno = $alumnosTable->where('ID', $asoc->ID_Alumno);
            
            if ($alumno && $alumno['ID_Situacion'] <= 2) {
                // 3. Obtener curso y nivel de la base de datos de la institución
                $cursosTable = DatabaseManager::table('cursos', $institucionId);
                $nivelesTable = DatabaseManager::table('nivel', $institucionId); // Corregido: 'nivel' no 'niveles'
                
                $curso = $cursosTable->where('ID', $alumno['ID_Curso']);
                $nivel = $curso ? $nivelesTable->where('ID', $curso['ID_Nivel']) : null;
                
                $nombreNivel = $nivel ? $nivel['Nivel'] : 'Nivel no asignado';
                $nombreCurso = $curso ? $curso['Cursos'] : 'Curso no asignado';
                
                $estudiantes[] = [
                    'id_alumno' => $alumno['ID'],
                    'estudiante' => $alumno['Apellido'] . ', ' . $alumno['Nombre'],
                    'dni' => $alumno['DNI'],
                    'curso_completo' => "{$nombreCurso} ({$nombreNivel})",
                ];
            }
        }
        
        return collect($estudiantes);
    }
}
