<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaMateriaMuro extends Model
{
    protected $table = 'tareas_materia_muro';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Materia',
        'ID_Curso',
        'ID_Usuario',
        'Titulo',
        'Fecha',
        'Hora',
        'B',
        'Fecha_B',
        'ID_Usuario_B',
        'Tipo_Materia',
    ];

    protected $casts = [
        'ID' => 'integer',
        'ID_Materia' => 'integer',
        'ID_Curso' => 'integer',
        'ID_Usuario' => 'integer',
        'Fecha' => 'date:Y-m-d',
        'Hora' => 'string', // TIME
        'B' => 'integer',
        'Fecha_B' => 'date:Y-m-d',
        'ID_Usuario_B' => 'integer',
    ];

    public function scopeActivos($query)
    {
        return $query->where('B', 0);
    }

    public function scopeTipoMateria($query, string $tipo)
    {
        return $query->where('Tipo_Materia', strtolower(trim($tipo))); // c/g
    }
}
