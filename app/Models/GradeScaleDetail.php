<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeScaleDetail extends Model
{
    protected $connection = 'tenant';

    protected $table = 'calificaciones_escalas_detalle';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Escala',
        'Estado'
    ];

    protected $casts = [
        'ID_Escala' => 'integer'
    ];

    /**
     * Get the grade scale
     */
    public function scale()
    {
        return $this->belongsTo(GradeScale::class, 'ID_Escala', 'ID');
    }
}
