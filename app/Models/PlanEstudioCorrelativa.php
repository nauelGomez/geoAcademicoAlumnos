<?php
// app/Models/PlanEstudioCorrelativa.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanEstudioCorrelativa extends Model
{
    protected $table = 'planes_estudio_correlativas';
    public $timestamps = false;
    
    // Relación con la materia correlativa
    public function materiaCorrelativa()
    {
        return $this->belongsTo(MateriaPlan::class, 'ID_Materia_C', 'ID');
    }
}

// app/Models/MateriaPlan.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MateriaPlan extends Model
{
    protected $table = 'materias_planes';
    public $timestamps = false;
}