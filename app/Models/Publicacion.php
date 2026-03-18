<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publicacion extends Model
{
    protected $connection = 'tenant';

    protected $table = 'publicaciones';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    public function detalles()
    {
        return $this->hasMany(PublicacionDetalle::class, 'ID_Comunicado', 'ID');
    }
}
