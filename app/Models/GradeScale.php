<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeScale extends Model
{
    protected $connection = 'tenant';

    protected $table = 'calificaciones_escalas';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Escala',
        'Tipo'
    ];

    protected $casts = [
        'Tipo' => 'integer'
    ];

    /**
     * Get the scale details
     */
    public function details()
    {
        return $this->hasMany(GradeScaleDetail::class, 'ID_Escala', 'ID');
    }

    /**
     * Get conceptual grade for a numeric value
     */
    public function getConceptualGrade($numericGrade)
    {
        if ($this->Tipo != 2) {
            return $numericGrade; // Not a conceptual scale
        }

        $roundedGrade = round($numericGrade);
        $detail = $this->details()->where('ID', $roundedGrade)->first();
        
        return $detail ? $detail->Estado : $numericGrade;
    }

    /**
     * Check if this is a conceptual scale
     */
    public function isConceptual()
    {
        return $this->Tipo == 2;
    }
}
