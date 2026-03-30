<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamBoard extends Model
{
    protected $connection = 'tenant';

    protected $table = 'mesas_examen';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    public function titular()
    {
        return $this->belongsTo(Teacher::class, 'ID_Titular', 'ID');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'ID_Materia', 'ID');
    }
}
