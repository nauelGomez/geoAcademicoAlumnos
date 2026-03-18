<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamPeriod extends Model
{
    protected $connection = 'tenant';

    protected $table = 'mesas_examen_periodos';
    protected $primaryKey = 'ID';
    public $timestamps = false;
}
