<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Student extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'alumnos';
    protected $primaryKey = 'ID';
    public $timestamps = false;

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
        'Fecha_de_nacimiento' => 'date',
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

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'ID_Curso', 'ID');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'ID_Grupo', 'ID');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'ID_Nivel', 'ID');
    }

    public function situation(): BelongsTo
    {
        return $this->belongsTo(Situation::class, 'ID_Situacion', 'ID');
    }

    public function taskResolutions(): HasMany
    {
        return $this->hasMany(TaskResolution::class, 'ID_Alumno', 'ID');
    }

    public function taskSubmissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class, 'ID_Destinatario', 'ID');
    }

    public function taskQueries(): HasMany
    {
        return $this->hasMany(TaskQuery::class, 'ID_Alumno', 'ID');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'grupos', 'ID_Alumno', 'ID_Grupo', 'ID', 'ID');
    }

    public function subjectGroups(): BelongsToMany
    {
        return $this->belongsToMany(SubjectGroup::class, 'grupos', 'ID_Alumno', 'ID_Materia_Grupal', 'ID', 'ID');
    }

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
