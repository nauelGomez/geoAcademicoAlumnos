<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    protected $table = 'cursos';
    // Desactivamos timestamps si tu tabla vieja no tiene created_at / updated_at
    public $timestamps = false; 

    // Relación con el Plan de Estudios (lo pide el repository: 'curso.plan')
    public function plan()
    {
        return $this->belongsTo(PlanEstudio::class, 'ID_Plan');
    }
}