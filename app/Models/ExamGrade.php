<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamGrade extends Model
{
    protected $connection = 'tenant';

    protected $table = 'notas_mesa_examen';
    protected $primaryKey = 'ID';
    public $timestamps = false;
}
