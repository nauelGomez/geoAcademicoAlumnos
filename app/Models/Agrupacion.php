<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agrupacion extends Model
{
    protected $table = 'agrupaciones';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Grupo',
        'ID_Curso',
        'Alternancia',
        'ID_Ciclo_Lectivo',
    ];

    protected $casts = [
        'ID' => 'int',
        'ID_Curso' => 'int',
        'Alternancia' => 'int',
        'ID_Ciclo_Lectivo' => 'int',
    ];

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'ID_Curso', 'ID');
    }

    public function vencimientosTareas()
    {
        return $this->hasMany(TareaVirtualVencimiento::class, 'ID_Agrupacion', 'ID');
    }
}
