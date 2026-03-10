<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceState extends Model
{
    protected $connection = 'tenant';

    protected $table = 'estado';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Estado',
        'Incidencia'
    ];

    protected $casts = [
        'Incidencia' => 'decimal:2'
    ];

    /**
     * Get attendance records for this state
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'ID_Estado', 'ID');
    }

    /**
     * Get group attendance records for this state
     */
    public function attendanceGroups()
    {
        return $this->hasMany(AttendanceGroup::class, 'ID_Estado', 'ID');
    }

    /**
     * Check if this is a present state
     */
    public function isPresent()
    {
        return $this->Estado === 'Presente';
    }

    /**
     * Check if this is a late state
     */
    public function isLate()
    {
        return $this->Estado === 'Tarde';
    }

    /**
     * Check if this is an absent state
     */
    public function isAbsent()
    {
        return in_array($this->Estado, [
            'Ausente', 'AusenteDT', 'AusenteRI', 'AusenteDTSC'
        ]);
    }

    /**
     * Check if this is a justified state
     */
    public function isJustified()
    {
        return in_array($this->Estado, [
            'Justificada', 'JustificadaDT', 'JustificadaDTSC'
        ]);
    }

    /**
     * Check if this is an early departure state
     */
    public function isEarlyDeparture()
    {
        return $this->Estado === 'Retiro';
    }

    /**
     * Check if this is an "other" state (Otras)
     */
    public function isOther()
    {
        return in_array($this->Estado, [
            'JustificadaDTSC', 'AusenteDTSC'
        ]);
    }

    /**
     * Get state category
     */
    public function getCategoryAttribute()
    {
        if ($this->isPresent()) {
            return 'present';
        } elseif ($this->isLate()) {
            return 'late';
        } elseif ($this->isEarlyDeparture()) {
            return 'early_departure';
        } elseif ($this->isJustified()) {
            return 'justified';
        } elseif ($this->isAbsent()) {
            return 'absent';
        } else {
            return 'other';
        }
    }

    /**
     * Scope to get present state
     */
    public function scopePresent($query)
    {
        return $query->where('Estado', 'Presente');
    }

    /**
     * Scope to get late state
     */
    public function scopeLate($query)
    {
        return $query->where('Estado', 'Tarde');
    }

    /**
     * Scope to get absent states
     */
    public function scopeAbsent($query)
    {
        return $query->whereIn('Estado', [
            'Ausente', 'AusenteDT', 'AusenteRI', 'AusenteDTSC'
        ]);
    }

    /**
     * Scope to get justified states
     */
    public function scopeJustified($query)
    {
        return $query->whereIn('Estado', [
            'Justificada', 'JustificadaDT', 'JustificadaDTSC'
        ]);
    }

    /**
     * Scope to get early departure state
     */
    public function scopeEarlyDeparture($query)
    {
        return $query->where('Estado', 'Retiro');
    }

    /**
     * Scope to get other states
     */
    public function scopeOther($query)
    {
        return $query->whereIn('Estado', [
            'JustificadaDTSC', 'AusenteDTSC'
        ]);
    }
}
