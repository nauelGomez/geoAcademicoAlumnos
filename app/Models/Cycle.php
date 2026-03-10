<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cycle extends Model
{
    protected $connection = 'tenant';
    protected $table = 'ciclo_lectivo';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Nivel',
        'Ciclo_lectivo',
        'Vigente'
    ];

    protected $casts = [
        'ID_Nivel' => 'integer',
        // 'Vigente' => 'boolean' // Quitamos esto si en la DB es 'SI'/'NO'
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class, 'ID_Ciclo_Lectivo', 'ID');
    }

    // Scope corregido para usar la columna real 'Vigente' con 'SI'
    public function scopeActive($query)
    {
        return $query->where('Vigente', 'SI');
    }

    public function scopeByLevel($query, $levelId)
    {
        return $query->where('ID_Nivel', $levelId);
    }
}