<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

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

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'ID_Nivel', 'ID');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'ID_Curso', 'ID');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'ID_Curso', 'ID');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'ID_Curso', 'ID');
    }

    public function scopeByLevel($query, $levelId)
    {
        return $query->where('ID_Nivel', $levelId);
    }
}
