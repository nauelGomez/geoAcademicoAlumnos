<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsWall extends Model
{
    protected $connection = 'tenant';

    protected $table = 'tareas_materia_muro';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Materia',
        'ID_Usuario',
        'ID_Curso',
        'Fecha',
        'Titulo',
        'Tipo_Materia',
        'B'
    ];

    protected $casts = [
        'ID_Materia' => 'integer',
        'ID_Usuario' => 'integer',
        'ID_Curso' => 'integer',
        'Fecha' => 'date',
        'B' => 'boolean'
    ];

    /**
     * Get the teacher who created the news
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'ID_Usuario', 'ID');
    }

    /**
     * Get the subject (if it's a regular subject)
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'ID_Materia', 'ID');
    }

    /**
     * Get the group subject (if it's a group subject)
     */
    public function groupSubject()
    {
        return $this->belongsTo(GroupSubject::class, 'ID_Materia', 'ID');
    }

    /**
     * Get the news details
     */
    public function details()
    {
        return $this->hasMany(NewsWallDetail::class, 'ID_Muro', 'ID');
    }

    /**
     * Get unread count for a student
     */
    public function getUnreadCount($studentId)
    {
        return $this->details()
            ->where('Tipo_Usuario', 'D')
            ->where('B', 0)
            ->whereDoesntHave('reads', function($query) use ($studentId) {
                $query->where('ID_Alumno', $studentId);
            })
            ->count();
    }

    /**
     * Check if news has unread items for student
     */
    public function hasUnreadForStudent($studentId)
    {
        return $this->getUnreadCount($studentId) > 0;
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute()
    {
        return $this->Fecha ? $this->Fecha->format('d/m/Y') : '';
    }

    /**
     * Get subject name based on type
     */
    public function getSubjectNameAttribute()
    {
        if (empty($this->ID_Materia)) {
            return 'General';
        }

        if ($this->Tipo_Materia === 'g') {
            return $this->groupSubject ? $this->groupSubject->Materia : 'Materia Grupal';
        }

        return $this->subject ? $this->subject->Materia : 'Materia';
    }

    /**
     * Scope to get active news
     */
    public function scopeActive($query)
    {
        return $query->where('B', 0);
    }

    /**
     * Scope to get news for a specific course
     */
    public function scopeForCourse($query, $courseId)
    {
        return $query->where('ID_Curso', $courseId);
    }

    /**
     * Scope to get general news (no specific course)
     */
    public function scopeGeneral($query)
    {
        return $query->where('ID_Curso', 0);
    }

    /**
     * Scope to get news after a specific date
     */
    public function scopeAfterDate($query, $date)
    {
        return $query->where('Fecha', '>=', $date);
    }

    /**
     * Scope to get group subjects
     */
    public function scopeGroupSubjects($query)
    {
        return $query->where('Tipo_Materia', 'g');
    }
}
