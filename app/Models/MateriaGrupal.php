<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MateriaGrupal extends Model
{
    protected $table = 'materias_grupales';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = ['ID_Ciclo_Lectivo', 'ID_Materia', 'ID_Personal', 'Cupo', 'AI'];

    public function docente()
    {
        return $this->belongsTo(Personal::class, 'ID_Personal', 'ID');
    }

    public function inscripciones()
    {
        return $this->hasMany(Grupo::class, 'ID_Materia_Grupal');
    }
}