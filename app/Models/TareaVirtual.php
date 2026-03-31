<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaVirtual extends Model
{
    protected $table = 'tareas_virtuales';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Titulo',
        'Consigna',
        'ID_Curso',
        'ID_Materia',
        'Tipo_Materia',
        'ID_Ciclo_Lectivo',
        'ID_Usuario',
        'Fecha',
        'Envio',
        'Tipo',
        'Dest_Sel',
        'Cerrada',
        'Fecha_Vencimiento',
        'Hora_Vencimiento',
        'Fecha_Publicacion',
        'Hora_Publicacion',
        'ID_Clase',
    ];

    protected $casts = [
        'ID' => 'integer',
        'ID_Curso' => 'integer',
        'ID_Materia' => 'integer',
        'ID_Ciclo_Lectivo' => 'integer',
        'ID_Usuario' => 'integer',
        'Fecha' => 'date:Y-m-d',
        'Envio' => 'integer',
        'Tipo' => 'integer',
        'Dest_Sel' => 'integer',
        'Cerrada' => 'integer',
        'Fecha_Vencimiento' => 'date:Y-m-d',
        'Hora_Vencimiento' => 'string',   // TIME
        'Fecha_Publicacion' => 'date:Y-m-d',
        'Hora_Publicacion' => 'string',   // TIME
        'ID_Clase' => 'integer',
    ];

    // Relaciones
    public function envios()
    {
        return $this->hasMany(TareaEnvio::class, 'ID_Tarea', 'ID');
    }

    public function consultas()
    {
        return $this->hasMany(TareaConsulta::class, 'ID_Tarea', 'ID');
    }

    // Scopes
    public function scopeAbiertas($query)
    {
        return $query->where('Cerrada', 0);
    }

    public function scopePublicadas($query)
    {
        return $query->where('Envio', 1);
    }

    public function scopeTipoMateria($query, string $tipo)
    {
        return $query->where('Tipo_Materia', strtolower(trim($tipo))); // c/g
    }
}
