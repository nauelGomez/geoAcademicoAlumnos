<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaVirtualVencimiento extends Model
{
    protected $table = 'tareas_virtuales_vencimientos';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Tarea',
        'Tipo',
        'ID_Agrupacion',
        'Fecha_Vencimiento',
        'Hora_Vencimiento',
    ];

    protected $casts = [
        'ID' => 'int',
        'ID_Tarea' => 'int',
        'Tipo' => 'int',
        'ID_Agrupacion' => 'int',
        'Fecha_Vencimiento' => 'date:Y-m-d',
    ];

    public function tarea()
    {
        return $this->belongsTo(TareaVirtual::class, 'ID_Tarea', 'ID');
    }

    public function agrupacion()
    {
        return $this->belongsTo(Agrupacion::class, 'ID_Agrupacion', 'ID');
    }
}
