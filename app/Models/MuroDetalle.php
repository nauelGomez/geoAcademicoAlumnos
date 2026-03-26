<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MuroDetalle extends Model
{
    protected $table = 'tareas_materia_muro_detalle';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = ['ID_Muro', 'ID_Usuario', 'Tipo_Usuario', 'Fecha', 'Hora', 'Mensaje', 'B'];

    protected $casts = [
        'ID' => 'integer',
        'ID_Muro' => 'integer',
        'ID_Usuario' => 'integer',
        'B' => 'integer',
    ];

    /**
     * Obtener las lecturas asociadas a este detalle.
     */
    public function lecturas()
    {
        return $this->hasMany(MuroLectura::class, 'ID_Muro_Detalle', 'ID');
    }

    /**
     * Obtener el muro padre.
     */
    public function muro()
    {
        return $this->belongsTo(Muro::class, 'ID_Muro', 'ID');
    }

    /**
     * Obtener el usuario (docente o alumno) que hizo la intervención.
     */
    public function usuario()
    {
        // Según Tipo_Usuario: 'D' = Personal (docente), 'A' = Alumno
        if ($this->Tipo_Usuario === 'D') {
            return $this->belongsTo(Personal::class, 'ID_Usuario', 'ID');
        } else {
            return $this->belongsTo(Alumno::class, 'ID_Usuario', 'ID');
        }
    }

    /**
     * Scope para obtener detalles vigentes (no eliminados).
     */
    public function scopeActive($query)
    {
        return $query->where('B', 0);
    }

    /**
     * Scope para obtener intervenciones de docentes.
     */
    public function scopeFromTeacher($query)
    {
        return $query->where('Tipo_Usuario', 'D');
    }

    /**
     * Scope para obtener intervenciones de alumnos.
     */
    public function scopeFromStudent($query)
    {
        return $query->where('Tipo_Usuario', 'A');
    }
}
