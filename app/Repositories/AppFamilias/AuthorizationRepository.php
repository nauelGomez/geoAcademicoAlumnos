<?php

namespace App\Repositories\AppFamilias;

use Illuminate\Support\Facades\DB;

class AuthorizationRepository
{
    /**
     * Valida si la familia tiene acceso al alumno (Seguridad legacy)
     */
    public function validateFamilyStudent($familyId, $studentId)
    {
        // En el sistema viejo esto se validaba contra la sesión.
        // Aquí verificamos la relación en la tabla correspondiente.
        return DB::table('alumnos')
            ->where('ID', $studentId)
            ->where('ID_Usuario', $familyId) // Ajustar según tu nombre de columna
            ->exists();
    }

    /**
     * Obtener datos básicos de sesión del alumno
     */
    public function getStudentSessionData($studentId)
    {
        return DB::table('alumnos as a')
            ->join('nivel as n', 'a.ID_Nivel', '=', 'n.ID')
            ->where('a.ID', $studentId)
            ->select('a.ID', 'a.ID_Nivel', 'a.ID_Curso', 'n.Nivel')
            ->first();
    }
}