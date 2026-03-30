<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Muro extends Model
{
    protected $table = 'tareas_materia_muro';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = ['Titulo', 'ID_Materia', 'ID_Curso', 'Tipo_Materia', 'ID_Usuario', 'Fecha', 'B'];

    protected $casts = [
        'ID' => 'integer',
        'ID_Materia' => 'integer',
        'ID_Curso' => 'integer',
        'ID_Usuario' => 'integer',
        'B' => 'integer',
    ];

    /**
     * Obtener todos los detalles/intervenciones del muro (no eliminados).
     */
    public function detalles()
    {
        return $this->hasMany(MuroDetalle::class, 'ID_Muro', 'ID')
            ->where('B', 0)
            ->orderBy('ID', 'asc');
    }

    /**
     * Obtener el docente que creó el muro.
     */
    public function docente()
    {
        return $this->belongsTo(Personal::class, 'ID_Usuario', 'ID');
    }

    /**
     * Obtener la materia normal asociada.
     */
    public function materia()
    {
        return $this->belongsTo(Materia::class, 'ID_Materia', 'ID');
    }

    /**
     * Obtener la materia grupal asociada.
     */
    public function materiaGrupal()
    {
        return $this->belongsTo(MateriaGrupal::class, 'ID_Materia', 'ID');
    }

    /**
     * Scope para obtener muros vigentes (no eliminados).
     */
    public function scopeActive($query)
    {
        return $query->where('B', 0);
    }

    /**
     * Scope para obtener muros de un curso específico.
     */
    public function scopeOfCourse($query, $courseId)
    {
        return $query->where('ID_Curso', $courseId);
    }

    /**
     * Scope para obtener muros de materias grupales.
     */
    public function scopeGroupMatter($query)
    {
        return $query->where('Tipo_Materia', 'g')->where('ID_Curso', 0);
    }
}
