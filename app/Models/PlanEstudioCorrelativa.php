<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanEstudioCorrelativa extends Model
{
    protected $table = 'planes_estudio_correlativas';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    public function materiaRequerida()
    {
        // Solo la relación, NO la definición de la clase MateriaPlan acá
        return $this->belongsTo(MateriaPlan::class, 'ID_Materia_C', 'ID');
    }
}