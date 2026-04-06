<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaDevolucionAdjunto extends Model
{
    protected $table = 'tareas_devoluciones_adjuntos';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $guarded = ['ID'];

    protected $fillable = [
        'ID_Tarea',
        'ID_Alumno',
        'Archivo',
    ];

    protected $casts = [
        'ID'       => 'int',
        'ID_Tarea' => 'int',
        'ID_Alumno'=> 'int',
    ];
}
