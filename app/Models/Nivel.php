<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nivel extends Model
{
    // El nombre exacto de la tabla en tu BD
    protected $table = 'nivel';
    
    // Tu Primary Key
    protected $primaryKey = 'ID';
    public $incrementing = true;
    protected $keyType = 'int';
    
    // Asumo que esta tabla no usa created_at y updated_at
    public $timestamps = false;

    protected $fillable = [
        'Nivel',
        // Si hay más columnas relevantes, agregalas acá
    ];
}