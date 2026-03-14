<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $connection = 'tenant';

    protected $table = 'cursos';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Cursos',
        'ID_Nivel'
    ];

    protected $casts = [
        'ID_Nivel' => 'integer'
    ];

    // --- RELACIONES CON SINTAXIS "VINTAGE" ---

    public function level(): BelongsTo
    {
        return $this->belongsTo('App\Models\Level', 'ID_Nivel', 'ID');
    }

    public function students(): HasMany
    {
        return $this->hasMany('App\Models\Student', 'ID_Curso', 'ID');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany('App\Models\Subject', 'ID_Curso', 'ID');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany('App\Models\Task', 'ID_Curso', 'ID');
    }

    // --- SCOPES ---

    public function scopeByLevel($query, $levelId)
    {
        return $query->where('ID_Nivel', $levelId);
    }
}