<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Student extends Model
{
    protected $connection = 'tenant';

    protected $table = 'alumnos';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    // 🔥 CRÍTICO PARA LARAVEL 5.5: Definir las fechas
    protected $dates = [
        'Fecha_de_nacimiento'
    ];

    protected $fillable = [
        'Nombre',
        'Apellido',
        'DNI',
        'Fecha_de_nacimiento',
        'Sexo',
        'Orden',
        'ID_Curso',
        'ID_Grupo',
        'Direccion',
        'Telefono',
        'Telefono2',
        'ID_Situacion',
        'Nombre_Responsable',
        'Mail_Reponsable',
        'Codigo',
        'Codigo_Bio',
        'PP',
        'ID_Nivel',
        'Code_FC',
        'Perfil',
        'PPI',
        'LF',
        'FCE',
        'Edad'
    ];

    protected $casts = [
        'Orden' => 'integer',
        'ID_Curso' => 'integer',
        'ID_Grupo' => 'integer',
        'ID_Situacion' => 'integer',
        'Codigo_Bio' => 'integer',
        'ID_Nivel' => 'integer',
        'PPI' => 'integer',
        'LF' => 'integer',
        'FCE' => 'integer',
        'Edad' => 'integer'
    ];

    // --- RELACIONES CON SINTAXIS "VINTAGE" ---

    public function course(): BelongsTo
    {
        return $this->belongsTo('App\Models\Course', 'ID_Curso', 'ID');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo('App\Models\Group', 'ID_Grupo', 'ID');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo('App\Models\Level', 'ID_Nivel', 'ID');
    }

    public function situation(): BelongsTo
    {
        return $this->belongsTo('App\Models\Situation', 'ID_Situacion', 'ID');
    }

    public function taskResolutions(): HasMany
    {
        return $this->hasMany('App\Models\TaskResolution', 'ID_Alumno', 'ID');
    }

    public function taskSubmissions(): HasMany
    {
        return $this->hasMany('App\Models\TaskSubmission', 'ID_Destinatario', 'ID');
    }

    public function taskQueries(): HasMany
    {
        return $this->hasMany('App\Models\TaskQuery', 'ID_Alumno', 'ID');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Group', 'grupos', 'ID_Alumno', 'ID_Grupo', 'ID', 'ID');
    }

    public function subjectGroups(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\SubjectGroup', 'grupos', 'ID_Alumno', 'ID_Materia_Grupal', 'ID', 'ID');
    }

    // --- ACCESSORS Y SCOPES ---

    public function getFullNameAttribute(): string
    {
        return trim($this->Nombre . ' ' . $this->Apellido);
    }

    public function scopeActive($query)
    {
        return $query->where('ID_Situacion', 2);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('ID_Curso', $courseId);
    }

    public function scopeByLevel($query, $levelId)
    {
        return $query->where('ID_Nivel', $levelId);
    }

    public function getAgeAttribute(): int
    {
        return $this->Fecha_de_nacimiento ? $this->Fecha_de_nacimiento->age : 0;
    }
}