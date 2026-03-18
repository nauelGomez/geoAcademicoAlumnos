<?php

namespace App\Repositories;

use App\Models\Alumno;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ProfileRepository
{
    public function getProfile($studentId)
    {
        $alumno = Alumno::findOrFail($studentId);

        $carpeta = DB::table('institucion')->value('Carpeta');
        $pic = $alumno->Perfil ?: 'usuario.png';

        $picUrl = $carpeta ? "https://geoeducacion.com.ar/{$carpeta}/imagenes/usuarios/{$pic}" : "";

        return [
            'id' => $alumno->ID,
            'nombre' => $alumno->Nombre,
            'apellido' => $alumno->Apellido,
            'direccion' => $alumno->Direccion,
            'telefono' => $alumno->Telefono,
            'fecha_nacimiento' => $alumno->Fecha_de_nacimiento,
            'mail' => $alumno->Mail_Reponsable,
            'foto_perfil' => $picUrl,
        ];
    }

    public function updateProfile($studentId, $data)
    {
        $alumno = Alumno::findOrFail($studentId);

        $alumno->Direccion = $data['direccion'];
        $alumno->Telefono = $data['telefono'];
        $alumno->Fecha_de_nacimiento = $data['fdn'];

        if (!empty($data['pass'])) {
            $passwordColumn = $this->resolvePasswordColumn();
            if ($passwordColumn) {
                $alumno->{$passwordColumn} = $this->hashPassword($data['pass']);
            }
        }

        $alumno->save();

        return $this->getProfile($studentId);
    }

    private function resolvePasswordColumn()
    {
        $candidates = ['Password', 'Clave', 'Pass', 'Contrasena', 'Contrasenia'];

        foreach ($candidates as $col) {
            try {
                if (Schema::connection('tenant')->hasColumn('alumnos', $col)) {
                    return $col;
                }
            } catch (\Exception $e) {
                // ignore
            }
        }

        return null;
    }

    private function hashPassword($plain)
    {
        $useMd5 = env('LEGACY_PASSWORD_MD5', false);
        if ($useMd5) {
            return md5($plain);
        }

        return Hash::make($plain);
    }
}
