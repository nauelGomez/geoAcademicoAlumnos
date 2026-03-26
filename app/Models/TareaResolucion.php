<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaResolucion extends Model {
    protected $table = 'tareas_resoluciones';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $fillable = ['ID_Tarea', 'ID_Alumno', 'Resolucion', 'Fecha', 'Hora', 'Leido'];
}
