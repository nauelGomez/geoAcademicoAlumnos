<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    protected $connection = 'tenant';

    protected $table = 'personal';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Nombre',
        'Apellido',
        'DNI',
        'Mail',
        'Telefono',
        'Cargo',
        'Estado'
    ];

    protected $casts = [
        'Estado' => 'boolean'
    ];

    // --- RELACIONES CON SINTAXIS "VINTAGE" ---

    public function tasks(): HasMany
    {
        return $this->hasMany('App\Models\Task', 'ID_Usuario', 'ID');
    }

    public function virtualClasses(): HasMany
    {
        return $this->hasMany('App\Models\VirtualClass', 'ID_Usuario', 'ID');
    }

    public function taskQueries(): HasMany
    {
        return $this->hasMany('App\Models\TaskQuery', 'ID_Docente', 'ID');
    }

    public function newsWalls(): HasMany
    {
        return $this->hasMany('App\Models\NewsWall', 'ID_Usuario', 'ID');
    }

    // --- ACCESSORS Y SCOPES ---

    public function getFullNameAttribute(): string
    {
        return trim($this->Apellido . ' - ' . $this->Nombre);
    }

    public function scopeActive($query)
    {
        return $query->where('Estado', 1);
    }
}