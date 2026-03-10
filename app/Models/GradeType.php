<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeType extends Model
{
    protected $connection = 'tenant';

    protected $table = 'calificaciones';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Tipo'
    ];

    /**
     * Get the grade operations that use this type
     */
    public function operations()
    {
        return $this->hasMany(GradeOperation::class, 'ID_Calificacion', 'ID');
    }
}
