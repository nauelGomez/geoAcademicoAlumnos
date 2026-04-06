<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaForo extends Model
{
    protected $table = 'tareas_virtuales_foros';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Tarea',
        'ID_Usuario',
        'Tipo_Usuario',
        'Fecha',
        'Hora',
        'Mensaje',
        'B',
        'Leido',
        'Fecha_Leido',
        'Hora_Leido',
        'ID_Respuesta',
    ];

    protected $casts = [
        'ID'           => 'integer',
        'ID_Tarea'     => 'integer',
        'ID_Usuario'   => 'integer',
        'Tipo_Usuario' => 'integer',
        'B'            => 'integer',
        'Leido'        => 'integer',
        'ID_Respuesta' => 'integer',
    ];

    // Relaciones opcionales (por si después te sirve)
    public function foro()
    {
        return $this->belongsTo(TareaVirtual::class, 'ID_Tarea', 'ID');
    }

    public function respuestaA()
    {
        // el padre (si ID_Respuesta > 0)
        return $this->belongsTo(self::class, 'ID_Respuesta', 'ID');
    }

    public function respuestas()
    {
        // hijos
        return $this->hasMany(self::class, 'ID_Respuesta', 'ID');
    }

    public function adjuntos()
    {
        return $this->hasMany(\App\Models\TareaForoAdjunto::class, 'ID_Intervencion', 'ID');
    }
}
