<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubjectGroup extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'materias_grupales';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Materia'
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'ID_Materia', 'ID');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class, 'ID_Materia_Grupal', 'ID');
    }
}
