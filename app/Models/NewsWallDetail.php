<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsWallDetail extends Model
{
    protected $connection = 'tenant';

    protected $table = 'tareas_materia_muro_detalle';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Muro',
        'Tipo_Usuario',
        'B'
    ];

    protected $casts = [
        'ID_Muro' => 'integer',
        'B' => 'boolean'
    ];

    /**
     * Get the news wall
     */
    public function newsWall()
    {
        return $this->belongsTo(NewsWall::class, 'ID_Muro', 'ID');
    }

    /**
     * Get the reads for this detail
     */
    public function reads()
    {
        return $this->hasMany(NewsWallRead::class, 'ID_Muro_Detalle', 'ID');
    }

    /**
     * Check if student has read this detail
     */
    public function isReadByStudent($studentId)
    {
        return $this->reads()->where('ID_Alumno', $studentId)->exists();
    }

    /**
     * Scope to get active details
     */
    public function scopeActive($query)
    {
        return $query->where('B', 0);
    }

    /**
     * Scope to get teacher details
     */
    public function scopeTeacher($query)
    {
        return $query->where('Tipo_Usuario', 'D');
    }
}
