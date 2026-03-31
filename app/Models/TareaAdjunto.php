<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaAdjunto extends Model
{
    protected $table = 'tareas_adjuntos';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Tarea',
        'Titulo',
        'Archivo',
    ];

    protected $casts = [
        'ID' => 'integer',
        'ID_Tarea' => 'integer',
    ];

    public function tarea()
    {
        return $this->belongsTo(TareaVirtual::class, 'ID_Tarea', 'ID');
    }
}
