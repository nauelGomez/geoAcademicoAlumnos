<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alumno extends Model
{
    protected $table = 'alumnos';
    protected $primaryKey = 'ID';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'Nombre', 'Apellido', 'DNI', 'Fecha_de_nacimiento', 'Sexo',
        'Orden', 'ID_Curso', 'ID_Grupo', 'Direccion', 'Telefono',
        'Telefono2', 'ID_Situacion', 'Nombre_Responsable',
        'Mail_Reponsable', 'Codigo', 'Codigo_Bio', 'PP',
        'ID_Nivel', 'Code_FC', 'Perfil', 'PPI', 'LF', 'FCE',
    ];

    protected $casts = [
        'ID'                  => 'integer',
        'Orden'               => 'integer',
        'ID_Curso'            => 'integer',
        'ID_Grupo'            => 'integer',
        'ID_Situacion'        => 'integer',
        'Codigo_Bio'          => 'integer',
        'ID_Nivel'            => 'integer',
        'PPI'                 => 'integer',
        'FCE'                 => 'integer',
        'Fecha_de_nacimiento' => 'date',
    ];

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'ID_Curso', 'ID'); 
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class, 'ID_Nivel', 'ID');
    }

    public function notasCursada()
    {
        return $this->hasMany(NotaCursada::class, 'ID_Alumno', 'ID');
    }
}