<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $connection = 'tenant';

    protected $table = 'notas_parciales';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Alumno',
        'Operacion',
        'Calificacion',
        'FECHA',
        'Observaciones',
        'B'
    ];

    protected $casts = [
        'Calificacion' => 'decimal:2',
        'FECHA' => 'date',
        'B' => 'boolean'
    ];

    /**
     * Get the student that owns the grade
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'ID_Alumno', 'ID');
    }

    /**
     * Get the grade operation
     */
    public function operation()
    {
        return $this->belongsTo(GradeOperation::class, 'Operacion', 'ID');
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute()
    {
        return $this->FECHA ? $this->FECHA->format('d/m/Y') : '';
    }

    /**
     * Get conceptual grade if applicable
     */
    public function getConceptualGradeAttribute()
    {
        if ($this->operation && $this->operation->scale && $this->operation->scale->Tipo == 2) {
            return $this->operation->scale->details()
                ->where('ID', round($this->Calificacion))
                ->value('Estado');
        }
        return $this->Calificacion;
    }

    /**
     * Scope to get active grades
     */
    public function scopeActive($query)
    {
        return $query->where('B', 0);
    }
}
