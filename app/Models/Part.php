<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    protected $connection = 'tenant';

    protected $table = 'partes';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Curso',
        'ID_Ciclo',
        'FECHA'
    ];

    protected $casts = [
        'ID_Curso' => 'integer',
        'ID_Ciclo' => 'integer',
        'FECHA' => 'date'
    ];

    /**
     * Get the course
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'ID_Curso', 'ID');
    }

    /**
     * Get the cycle
     */
    public function cycle()
    {
        return $this->belongsTo(Cycle::class, 'ID_Ciclo', 'ID');
    }

    /**
     * Get attendance records for this part
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'ID_Parte', 'ID');
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute()
    {
        return $this->FECHA ? $this->FECHA->format('d/m/Y') : '';
    }

    /**
     * Scope to get parts for a specific course
     */
    public function scopeForCourse($query, $courseId)
    {
        return $query->where('ID_Curso', $courseId);
    }

    /**
     * Scope to get parts for a specific cycle
     */
    public function scopeForCycle($query, $cycleId)
    {
        return $query->where('ID_Ciclo', $cycleId);
    }

    /**
     * Scope to get parts ordered by date
     */
    public function scopeOrderByDate($query, $direction = 'desc')
    {
        return $query->orderBy('FECHA', $direction);
    }

    /**
     * Scope to get parts after a specific date
     */
    public function scopeAfterDate($query, $date)
    {
        return $query->where('FECHA', '>=', $date);
    }

    /**
     * Scope to get parts before a specific date
     */
    public function scopeBeforeDate($query, $date)
    {
        return $query->where('FECHA', '<=', $date);
    }
}
