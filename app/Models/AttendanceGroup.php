<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceGroup extends Model
{
    protected $connection = 'tenant';

    protected $table = 'asistencia_grupal';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Alumnos',
        'ID_Estado',
        'ID_Materia',
        'ID_Ciclo_Lectivo',
        'Fecha',
        'Observaciones',
        'Constancia',
        'SAF'
    ];

    protected $casts = [
        'ID_Alumnos' => 'integer',
        'ID_Estado' => 'integer',
        'ID_Materia' => 'integer',
        'ID_Ciclo_Lectivo' => 'integer',
        'SAF' => 'integer',
        'Fecha' => 'date'
    ];

    /**
     * Get the student
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'ID_Alumnos', 'ID');
    }

    /**
     * Get the attendance state
     */
    public function state()
    {
        return $this->belongsTo(AttendanceState::class, 'ID_Estado', 'ID');
    }

    /**
     * Get the group subject
     */
    public function groupSubject()
    {
        return $this->belongsTo(GroupSubject::class, 'ID_Materia', 'ID');
    }

    /**
     * Get the cycle
     */
    public function cycle()
    {
        return $this->belongsTo(Cycle::class, 'ID_Ciclo_Lectivo', 'ID');
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute()
    {
        return $this->Fecha ? $this->Fecha->format('d/m/Y') : '';
    }

    /**
     * Get SAF description
     */
    public function getSafDescriptionAttribute()
    {
        $safDescriptions = [
            1 => 'SAF: Indumentaria',
            2 => 'SAF: Malestar',
            3 => 'SAF: Certificado'
        ];

        return $safDescriptions[$this->SAF] ?? '';
    }

    /**
     * Check if attendance is present
     */
    public function isPresent()
    {
        return $this->state && $this->state->Estado === 'Presente';
    }

    /**
     * Check if attendance is late
     */
    public function isLate()
    {
        return $this->state && $this->state->Estado === 'Tarde';
    }

    /**
     * Check if attendance is absent
     */
    public function isAbsent()
    {
        return $this->state && in_array($this->state->Estado, ['AusenteDT', 'AusenteDTSC']);
    }

    /**
     * Check if attendance is justified
     */
    public function isJustified()
    {
        return $this->state && in_array($this->state->Estado, ['JustificadaDT', 'JustificadaDTSC']);
    }

    /**
     * Check if attendance is other (Otras)
     */
    public function isOther()
    {
        return $this->state && in_array($this->state->Estado, ['JustificadaDTSC', 'AusenteDTSC']);
    }

    /**
     * Get incidence value
     */
    public function getIncidenceValue()
    {
        return $this->state ? $this->state->Incidencia : 0;
    }

    /**
     * Scope to get attendance for a specific student
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('ID_Alumnos', $studentId);
    }

    /**
     * Scope to get attendance for a specific cycle
     */
    public function scopeForCycle($query, $cycleId)
    {
        return $query->where('ID_Ciclo_Lectivo', $cycleId);
    }

    /**
     * Scope to get attendance between dates
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where('Fecha', '>=', $startDate)
                    ->where('Fecha', '<=', $endDate);
    }

    /**
     * Scope to get attendance by state
     */
    public function scopeByState($query, $stateId)
    {
        return $query->where('ID_Estado', $stateId);
    }
}
