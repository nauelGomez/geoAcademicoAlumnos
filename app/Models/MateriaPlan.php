<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MateriaPlan extends Model
{
    protected $table = 'materias_planes';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    public function correlativas()
    {
        // El campo 'B' en el PHP puro parece ser un borrado lógico (B=0 es activo)
        return $this->hasMany(PlanEstudioCorrelativa::class, 'ID_Materia', 'ID')->where('B', 0);
    }

    public function getQueueableRelations()
    {
        return [];
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        return null;
    }
}
