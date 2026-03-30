<?php

namespace App\Repositories\AppFamilias;

use Illuminate\Support\Facades\DB;

class ProfileRepository
{
    public function getProfileData($studentId)
    {
        $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
        if (!$alumno) return null;

        $institucion = DB::table('institucion')->first();
        $pic = $alumno->Perfil ?: 'usuario.png';
        
        return [
            'id' => $alumno->ID,
            'apellido' => $alumno->Apellido,
            'nombre' => $alumno->Nombre,
            'direccion' => $alumno->Direccion,
            'telefono' => $alumno->Telefono,
            'foto_url' => "https://geoeducacion.com.ar/{$institucion->Carpeta}/imagenes/usuarios/{$pic}"
        ];
    }

    public function updateProfile($studentId, $familyId, $data)
    {
        // 1. Actualizar datos académicos (BDD Institución)
        DB::table('alumnos')->where('ID', $studentId)->update([
            'Direccion' => $data['direccion'],
            'Telefono'  => $data['telefono']
        ]);

        // 2. Si hay password, actualizar en BDD Central (conexión 'mysql_gral')
        // El legacy usa MD5 y la tabla 'users'
        if (!empty($data['password'])) {
            $md5Pass = md5($data['password']);
            
            // Usamos la conexión mysql_gral definida en config/database.php
            DB::connection('mysql_gral')->table('users')
                ->where('id', $familyId)
                ->update(['password' => $md5Pass]);

            // Registrar el cambio de contraseña
            DB::connection('mysql_gral')->table('cambio_contrasena')->insert([
                'ID_Usuario' => $familyId,
                'Tipo_Usuario' => 2,
                'Fecha' => date('Y-m-d'),
                'IP' => request()->ip()
            ]);
        }
        return true;
    }

    public function updatePhotoRecord($studentId, $fileName)
    {
        return DB::table('alumnos')->where('ID', $studentId)->update(['Perfil' => $fileName]);
    }
}
