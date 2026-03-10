<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupSubject extends Model
{
    protected $connection = 'tenant';

    protected $table = 'materias_grupales';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Materia',
        'ID_Personal',
        'ID_Ciclo_Lectivo'
    ];

    protected $casts = [
        'ID_Personal' => 'integer',
        'ID_Ciclo_Lectivo' => 'integer'
    ];

    /**
     * Get the teacher for this subject
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'ID_Personal', 'ID');
    }

    /**
     * Get the academic cycle
     */
    public function cycle()
    {
        return $this->belongsTo(Cycle::class, 'ID_Ciclo_Lectivo', 'ID');
    }

    /**
     * Get the grade operations for this subject
     */
    public function gradeOperations()
    {
        return $this->hasMany(GradeOperation::class, 'ID_Materia', 'ID');
    }

    /**
     * Get the student groups for this subject
     */
    public function studentGroups()
    {
        return $this->hasMany(StudentGroup::class, 'ID_Materia_Grupal', 'ID');
    }

    /**
     * Get students enrolled in this subject
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'grupos', 'ID_Materia_Grupal', 'ID_Alumno')
                    ->wherePivot('ID_Ciclo_Lectivo', $this->ID_Ciclo_Lectivo);
    }

    /**
     * Check if a student is enrolled in this subject
     */
    public function hasStudent($studentId)
    {
        return $this->studentGroups()
                    ->where('ID_Alumno', $studentId)
                    ->where('ID_Ciclo_Lectivo', $this->ID_Ciclo_Lectivo)
                    ->exists();
    }

    /**
     * Scope to get subjects for a specific cycle
     */
    public function scopeForCycle($query, $cycleId)
    {
        return $query->where('ID_Ciclo_Lectivo', $cycleId);
    }

    /**
     * Get teacher's last name
     */
    public function getTeacherLastNameAttribute()
    {
        return $this->teacher ? $this->teacher->Apellido : '';
    }

    /**
     * Get display name with teacher
     */
    public function getDisplayNameAttribute()
    {
        $teacherName = $this->teacher ? " - Prof. {$this->teacher->Apellido}" : '';
        return $this->Materia . $teacherName;
    }
}
