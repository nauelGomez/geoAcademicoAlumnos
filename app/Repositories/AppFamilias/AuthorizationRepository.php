<?php

namespace App\Repositories\AppFamilias;

use Illuminate\Support\Facades\DB;

class AuthorizationRepository
{
    public function getData($studentId, $familyId)
    {
        $hoy = date('Y-m-d');
        $alumno = DB::table('alumnos')->where('ID', $studentId)->first();
        if (!$alumno) return null;

        // Verificar si la función está habilitada para este nivel
        $isEnabled = DB::table('nivel_parametros')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->value('Aut_App');

        // 1. Personas Autorizadas Vigentes (Anuales o Diarias)
        $persons = DB::table('autorizacion_retiro')
            ->where('ID_Alumno', $studentId)
            ->where('B', 0)
            ->where('Fecha', '>=', '2024-02-28') // Filtro según legacy
            ->get()
            ->map(function($p) use ($familyId) {
                return [
                    'id' => $p->ID,
                    'nombre_completo' => "{$p->Apellido}, {$p->Nombre}",
                    'dni' => $p->DNI,
                    'vinculo' => $p->Vinculo,
                    'tipo' => ($p->Temporal == 1) ? 'Temporal' : 'Anual',
                    'es_propietario' => ($p->Tipo_Usuario == 2 && $p->ID_Usuario == $familyId)
                ];
            });

        // 2. Avisos de Retiro Puntuales (Filtrados por fecha hoy o futura)
        $notices = DB::table('autorizaciones_retiro_recibidas as arr')
            ->join('autorizacion_retiro as ar', 'arr.ID_Autorizado', '=', 'ar.ID')
            ->where('arr.ID_Alumno', $studentId)
            ->where('arr.B', 0)
            ->where('arr.Fecha_Retiro', '>=', $hoy)
            ->select('arr.*', 'ar.Apellido', 'ar.Nombre', 'ar.DNI')
            ->get()
            ->map(function($n) use ($familyId) {
                return [
                    'id' => $n->ID,
                    'fecha_retiro' => date('d/m/Y', strtotime($n->Fecha_Retiro)),
                    'autorizado' => "{$n->Apellido}, {$n->Nombre}",
                    'dni' => $n->DNI,
                    'detalle' => $n->Detalle,
                    'es_propietario' => ($n->ID_Responsable == $familyId)
                ];
            });

        return [
            'config' => ['enabled' => (bool)$isEnabled],
            'persons' => $persons,
            'notices' => $notices
        ];
    }

    public function storePerson($data)
    {
        return DB::table('autorizacion_retiro')->insertGetId([
            'ID_Alumno' => $data['student_id'],
            'Apellido'  => $data['apellido'],
            'Nombre'    => $data['nombre'],
            'DNI'       => $data['dni'],
            'Vinculo'   => $data['vinculo'],
            'Temporal'  => $data['es_temporal'] ? 1 : 0,
            'Fecha'     => date('Y-m-d'),
            'ID_Usuario'=> $data['family_id'],
            'Tipo_Usuario' => 2, // Familia
            'B' => 0
        ]);
    }

    public function storeNotice($data)
    {
        return DB::table('autorizaciones_retiro_recibidas')->insertGetId([
            'ID_Alumno'      => $data['student_id'],
            'ID_Autorizado'  => $data['id_autorizado'],
            'ID_Responsable' => $data['family_id'],
            'Fecha_Retiro'   => $data['fecha'],
            'Detalle'        => $data['observaciones'],
            'Fecha'          => date('Y-m-d'),
            'Hora'           => date('H:i:s'),
            'B'              => 0
        ]);
    }

    public function deletePerson($id, $familyId)
    {
        return DB::table('autorizacion_retiro')
            ->where('ID', $id)
            ->where('ID_Usuario', $familyId)
            ->update(['B' => 1]);
    }

    public function deleteNotice($id, $familyId)
    {
        return DB::table('autorizaciones_retiro_recibidas')
            ->where('ID', $id)
            ->where('ID_Responsable', $familyId)
            ->update(['B' => 1]);
    }
}
