<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualTest extends Model
{
    protected $table = 'tareas_tests';
    protected $primaryKey = 'ID'; // Faltaba esto
    public $timestamps = false;   // Y esto

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'ID_Materia', 'ID');
    }

    public function docente()
    {
        return $this->belongsTo(Teacher::class, 'ID_Usuario', 'ID');
    }

    public function envios()
    {
        return $this->hasMany(VirtualTestEnvio::class, 'ID_Tarea', 'ID');
    }

    public function resoluciones()
    {
        return $this->hasMany(VirtualTestResolucion::class, 'ID_Tarea', 'ID');
    }
}