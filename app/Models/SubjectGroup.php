<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubjectGroup extends Model
{
    protected $connection = 'tenant';

    protected $table = 'materias_grupales';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Materia'
    ];

    public function tasks(): HasMany
    {
        // Pasado a string
        return $this->hasMany('App\Models\Task', 'ID_Materia', 'ID');
    }

    public function groups(): HasMany
    {
        // Pasado a string (Acá estaba el error rojo)
        return $this->hasMany('App\Models\Group', 'ID_Materia_Grupal', 'ID');
    }
}