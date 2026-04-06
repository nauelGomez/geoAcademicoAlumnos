<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaResolucion extends Model
{
    protected $table = 'tareas_resoluciones';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Tarea',
        'ID_Alumno',
        'Resolucion',
        'Fecha',
        'Hora',
        'Leido',
        'Fecha_Leido',
        'Hora_Leido',
        'Correcion',
        'Comentario_Correccion',
        'Fecha_Correccion',
        'Hora_Correccion',
    ];

    protected $casts = [
        'ID' => 'integer',
        'ID_Tarea' => 'integer',
        'ID_Alumno' => 'integer',
        'Leido' => 'integer',
        'Correcion' => 'integer',
        'Fecha' => 'date:Y-m-d',
        'Hora' => 'string',
        'Fecha_Leido' => 'string',
        'Hora_Leido' => 'string',
        'Fecha_Correccion' => 'string',
        'Hora_Correccion' => 'string',
    ];

    public function tarea()
    {
        return $this->belongsTo(TareaVirtual::class, 'ID_Tarea', 'ID');
    }
}
