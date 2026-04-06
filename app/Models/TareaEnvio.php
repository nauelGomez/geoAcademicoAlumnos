<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaEnvio extends Model
{
    protected $table = 'tareas_envios';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Tarea',
        'ID_Destinatario',
        'Aleatorio',
        'Envio',
        'Leido',
        'Fecha_Leido',
        'Hora_Leido',
        'IP_Leido',
        'MailD',
        'Resuelto',
        'Corregido',
    ];

    protected $casts = [
        'ID' => 'integer',
        'ID_Tarea' => 'integer',
        'ID_Destinatario' => 'integer',
        'Envio' => 'integer',
        'Leido' => 'integer',
        'Fecha_Leido' => 'date:Y-m-d',
        'Hora_Leido' => 'string', // TIME
        'Resuelto' => 'integer',
        'Corregido' => 'integer',
    ];

    public function tarea()
    {
        return $this->belongsTo(TareaVirtual::class, 'ID_Tarea', 'ID');
    }

    public function scopeNoLeidos($query)
    {
        return $query->where('Leido', 0);
    }

    public function scopePendientesCorreccion($query)
    {
        return $query->where('Envio', 1)->where('Resuelto', 1)->where('Corregido', 0);
    }
}
