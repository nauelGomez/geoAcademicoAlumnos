<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    protected $connection = 'tenant';

    protected $table = 'nivel';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Nivel',
        'Numero',
        'CUE',
        'Sello_institucion',
        'Sello_Director',
        'Firma_Director'
    ];

    // --- RELACIONES CON SINTAXIS "VINTAGE" ---

    public function courses(): HasMany
    {
        return $this->hasMany('App\Models\Course', 'ID_Nivel', 'ID');
    }

    public function students(): HasMany
    {
        return $this->hasMany('App\Models\Student', 'ID_Nivel', 'ID');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany('App\Models\Subject', 'ID_Nivel', 'ID');
    }

    public function cycles(): HasMany
    {
        return $this->hasMany('App\Models\Cycle', 'ID_Nivel', 'ID');
    }

    public function multipleLevels(): HasMany
    {
        return $this->hasMany('App\Models\NivelMultiple', 'ID_Nivel', 'ID');
    }
}