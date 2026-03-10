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
        'Orden', 'ID_CursoIndex', 'ID_Grupo', 'Direccion', 'Telefono',
        'Telefono2', 'ID_SituacionIndex', 'Nombre_Responsable',
        'Mail_Reponsable', 'Codigo', 'Codigo_Bio', 'PP',
        'ID_NivelIndex', 'Code_FC', 'Perfil', 'PPI', 'LF', 'FCE',
    ];

    protected $casts = [
        'ID' => 'integer', // <-- FIX: Chau IDPrimary
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

    /**
     * Relación con Curso
     */
  /**
     * Relación con Curso
     */
    public function curso()
    {
        // FIX: Cambiado de 'ID_CursoIndex' a 'ID_Curso'
        return $this->belongsTo(Curso::class, 'ID_Curso', 'ID'); 
    }

    /**
     * Relación con NotasCursada
     */
    public function notasCursada()
    {
        // Asumiendo que la tabla de notas tiene un ID_Alumno
        return $this->hasMany(NotaCursada::class, 'ID_Alumno', 'ID');
    }
}