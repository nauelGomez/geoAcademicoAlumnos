<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthRepository
{
    /**
     * Valida el email contra el alumno o sus familias asociadas y devuelve el ID de Familia
     */
    public function validateAndGetFamilyId(string $email, string $aleatorio, $institutionId): ?int
    {
        // 1. Buscar al alumno por su código aleatorio
        $alumno = DB::table('alumnos')->where('Aleatorio', $aleatorio)->first();
        
        if (!$alumno) {
            return null; 
        }

        $isValid = false;
        $emailSanitizado = strtolower(trim($email));

        // 2. Validar contra el padre/madre principal. 
        // OJO: Se usa 'Mail_Reponsable' tal cual figura en la estructura de tu DB.
        if (strtolower(trim($alumno->Mail_Reponsable)) === $emailSanitizado) {
            $isValid = true;
        } else {
            // 3. Validar en familias_asociadas si no coincidió el principal
            $asociada = DB::table('familias_asociadas')
                ->where('ID_Alumno', $alumno->ID)
                ->where('Mail_Reponsable', $emailSanitizado)
                ->exists();

            if ($asociada) {
                $isValid = true;
            }
        }

        if (!$isValid) {
            return null; // El mail no coincide en ningún lado
        }

        // 4. Autodescubrimiento del ID_Familia en la DB general
        return DB::connection('mysql_gral')
            ->table('asociaciones')
            ->where('ID_Alumno', $alumno->ID)
            ->where('ID_Institucion', $institutionId)
            ->value('ID_Familia');
    }

    /**
     * Lógica para generar el Access Token.
     * Reemplazar según el sistema que uses (Sanctum, JWT, etc.)
     */
    public function generateTokenForFamily($familiaId): string
    {
        // Si usás Laravel Sanctum sería algo como:
        // $user = User::find($familiaId);
        // return $user->createToken('familia-token')->plainTextToken;

        // Placeholder para que no rompa
        return hash('sha256', Str::random(40) . $familiaId); 
    }
}