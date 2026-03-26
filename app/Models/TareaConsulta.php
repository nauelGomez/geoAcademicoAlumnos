<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaConsulta extends Model {
    protected $table = 'tareas_consultas';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $fillable = ['ID_Tarea', 'ID_Alumno', 'Tipo', 'ID_Usuario', 'Consulta', 'Fecha', 'Leido'];
}
