<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alumno extends Model
{
    protected $table = 'alumnos';
    protected $primaryKey = 'IDPrimary';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'Nombre',
        'Apellido',
        'DNI',
        'Fecha_de_nacimiento',
        'Sexo',
        'Orden',
        'ID_CursoIndex',
        'ID_Grupo',
        'Direccion',
        'Telefono',
        'Telefono2',
        'ID_SituacionIndex',
        'Nombre_Responsable',
        'Mail_Reponsable',
        'Codigo',
        'Codigo_Bio',
        'PP',
        'ID_NivelIndex',
        'Code_FC',
        'Perfil',
        'PPI',
        'LF',
        'FCE',
    ];

    protected $casts = [
        'IDPrimary' => 'integer',
        'Orden' => 'integer',
        'ID_CursoIndex' => 'integer',
        'ID_Grupo' => 'integer',
        'ID_SituacionIndex' => 'integer',
        'Codigo_Bio' => 'integer',
        'ID_NivelIndex' => 'integer',
        'PPI' => 'integer',
        'FCE' => 'integer',
        'Fecha_de_nacimiento' => 'date',
    ];
}
