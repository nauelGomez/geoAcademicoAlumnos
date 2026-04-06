<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaResolucionAdjunto extends Model
{
    protected $table = 'tareas_resoluciones_adjuntos';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Tarea',
        'ID_Alumno',
        'Archivo',
        'Fecha',
        'Hora',
        'Leido',
        'Servidor',
    ];

    protected $casts = [
        'ID' => 'integer',
        'ID_Tarea' => 'integer',
        'ID_Alumno' => 'integer',
        'Leido' => 'integer',
        'Servidor' => 'integer',
        'Fecha' => 'date:Y-m-d',
        'Hora' => 'string',
    ];

    public function resolucion()
    {
        // No hay FK directa por ID, se relaciona por (ID_Tarea, ID_Alumno)
        return $this->belongsTo(TareaResolucion::class, 'ID_Tarea', 'ID_Tarea')
            ->whereColumn('tareas_resoluciones.ID_Alumno', 'tareas_resoluciones_adjuntos.ID_Alumno');
    }
}
