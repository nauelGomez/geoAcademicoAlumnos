<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    // ... tu código existente ...

    /**
     * Relación con el Plan de Estudio
     */
    public function plan()
    {
        // Al ser DB Legacy, forzamos las llaves: (ModeloDestino, foreign_key_local, owner_key_destino)
        return $this->belongsTo(Plan::class, 'ID_Plan', 'ID');
    }
}