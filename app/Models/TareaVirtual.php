<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaVirtual extends Model {
    protected $table = 'tareas_virtuales';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    public function docente() {
        return $this->belongsTo(Personal::class, 'ID_Usuario', 'ID');
    }
}
