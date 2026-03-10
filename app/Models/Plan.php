<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    // 1. Apuntamos a la tabla legacy exacta
    protected $table = 'planes_estudio';

    // 2. Definimos la clave primaria
    protected $primaryKey = 'ID';

    // 3. Como es legacy, desactivamos los timestamps de Laravel
    public $timestamps = false;

    // 4. Campos fillables (basado en tu captura de pantalla)
    protected $fillable = [
        'Nombre',
        'ID_Nivel',
        'Basico',
        'Texto_Informe',
        'Titulo',
        'ID_Dependencia'
    ];
}