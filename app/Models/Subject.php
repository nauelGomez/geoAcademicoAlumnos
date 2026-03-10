<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectPlan extends Model
{
    protected $table = 'materias_planes';
    protected $primaryKey = 'ID';
    public $timestamps = false; // No hay created_at en tu imagen

    protected $fillable = [
        'ID_Plan', 'Materia', 'Curso', 'Orden', 'Vencimiento',
        'Por_As', 'Max_Inas', 'Ap_Cur', 'Ap_Fin', 'Ap_Prom'
    ];
}