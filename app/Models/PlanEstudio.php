<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanEstudio extends Model
{
    protected $table = 'planes_estudio';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    public function cursos()
    {
        return $this->hasMany(PlanEstudioCurso::class, 'ID_Plan', 'ID');
    }
}