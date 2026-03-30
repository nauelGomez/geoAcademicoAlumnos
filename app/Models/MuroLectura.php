<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MuroLectura extends Model
{
    protected $table = 'tareas_materia_muro_lecturas';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = ['ID_Alumno', 'ID_Muro_Detalle', 'Fecha_Leido', 'Hora_Leido'];

    protected $casts = [
        'ID' => 'integer',
        'ID_Alumno' => 'integer',
        'ID_Muro_Detalle' => 'integer',
    ];

    /**
     * Obtener el detalle del muro que fue leído.
     */
    public function muroDetalle()
    {
        return $this->belongsTo(MuroDetalle::class, 'ID_Muro_Detalle', 'ID');
    }

    /**
     * Obtener el alumno que leyó el mensaje.
     */
    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'ID_Alumno', 'ID');
    }
}
