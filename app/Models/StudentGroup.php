<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentGroup extends Model
{
    protected $connection = 'tenant';

    protected $table = 'grupos';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Alumno',
        'ID_Materia_Grupal',
        'ID_Ciclo_Lectivo'
    ];

    protected $casts = [
        'ID_Alumno' => 'integer',
        'ID_Materia_Grupal' => 'integer',
        'ID_Ciclo_Lectivo' => 'integer'
    ];

    /**
     * Get the student
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'ID_Alumno', 'ID');
    }

    /**
     * Get the group subject
     */
    public function groupSubject()
    {
        return $this->belongsTo(GroupSubject::class, 'ID_Materia_Grupal', 'ID');
    }

    /**
     * Get the academic cycle
     */
    public function cycle()
    {
        return $this->belongsTo(Cycle::class, 'ID_Ciclo_Lectivo', 'ID');
    }

    /**
     * Scope to get groups for a specific student
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('ID_Alumno', $studentId);
    }

    /**
     * Scope to get groups for a specific cycle
     */
    public function scopeForCycle($query, $cycleId)
    {
        return $query->where('ID_Ciclo_Lectivo', $cycleId);
    }
}
