<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asociacion extends Model
{

    protected $connection = 'mysql_gral';

    protected $table = 'asociaciones';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    public function alumno()
    {
        return $this->belongsTo(Alumno::class, 'ID_Alumno', 'ID');
    }
}
