<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanEstudioCurso extends Model
{
    protected $table = 'planes_estudio_cursos';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    public function materias()
    {
        return $this->hasMany(MateriaPlan::class, 'Curso', 'ID');
    }
}