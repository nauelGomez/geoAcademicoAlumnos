<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamInscription extends Model
{
    protected $connection = 'tenant';

    protected $table = 'mesas_examen_inscripcion';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    public function mesa()
    {
        return $this->belongsTo(ExamBoard::class, 'ID_Mesa', 'ID');
    }

    public function periodo()
    {
        return $this->belongsTo(ExamPeriod::class, 'ID_Periodo', 'ID');
    }
}
