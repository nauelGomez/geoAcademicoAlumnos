<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicacionDetalle extends Model
{
    protected $connection = 'tenant';

    protected $table = 'publicaciones_detalle';
    protected $primaryKey = 'ID';
    public $timestamps = false;
}
