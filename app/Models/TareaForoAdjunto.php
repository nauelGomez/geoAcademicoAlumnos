<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaForoAdjunto extends Model
{
    protected $table = 'tareas_foros_adjuntos';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Intervencion',
        'Archivo',
        'Servidor',
    ];

    public function intervencion()
    {
        return $this->belongsTo(TareaForo::class, 'ID_Intervencion', 'ID');
    }
}
