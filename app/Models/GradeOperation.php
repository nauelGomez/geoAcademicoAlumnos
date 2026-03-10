<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeOperation extends Model
{
    protected $connection = 'tenant';

    protected $table = 'notas_operaciones_grupales';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Materia',
        'Fecha',
        'Descripcion',
        'Escala',
        'Promediable',
        'ID_Calificacion',
        'ID_Ciclo_Lectivo',
        'B'
    ];

    protected $casts = [
        'Fecha' => 'date',
        'Promediable' => 'integer',
        'ID_Calificacion' => 'integer',
        'ID_Ciclo_Lectivo' => 'integer',
        'B' => 'boolean'
    ];

    /**
     * Get the subject that owns the grade operation
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'ID_Materia', 'ID');
    }

    /**
     * Get the grade type
     */
    public function gradeType()
    {
        return $this->belongsTo(GradeType::class, 'ID_Calificacion', 'ID');
    }

    /**
     * Get the grade scale
     */
    public function scale()
    {
        return $this->belongsTo(GradeScale::class, 'Escala', 'ID');
    }

    /**
     * Get the grades for this operation
     */
    public function grades()
    {
        return $this->hasMany(Grade::class, 'Operacion', 'ID');
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute()
    {
        return $this->Fecha ? $this->Fecha->format('d/m/Y') : '';
    }

    /**
     * Check if this operation is promediable
     */
    public function isPromediable()
    {
        return $this->Promediable != 2;
    }

    /**
     * Get the display type with non-promediable indicator
     */
    public function getDisplayTypeAttribute()
    {
        $type = $this->gradeType ? $this->gradeType->Tipo : '';
        
        if ($this->Promediable == 2) {
            // Check if non-promediable grades should be shown
            $levelParam = LevelParameter::where('ID_Nivel', $this->subject->ID_Nivel ?? null)->first();
            if ($levelParam && $levelParam->Pub_Cal_NP == 1) {
                return $type . ' (No Prom)';
            }
            return null; // Don't show if not allowed
        }
        
        return $type;
    }

    /**
     * Scope to get active operations
     */
    public function scopeActive($query)
    {
        return $query->where('B', 0);
    }

    /**
     * Scope to get operations for a specific cycle
     */
    public function scopeForCycle($query, $cycleId)
    {
        return $query->where('ID_Ciclo_Lectivo', $cycleId);
    }
}
