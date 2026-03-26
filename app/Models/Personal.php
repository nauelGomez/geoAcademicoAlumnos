<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    protected $table = 'personal';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Nombre',
        'Apellido',
        'DNI',
        'Mail',
        'Telefono',
        'Cargo',
        'Estado',
        'PIC'
    ];

    protected $casts = [
        'ID' => 'integer',
        'Estado' => 'boolean'
    ];

    /**
     * Obtener los muros creados por este docente.
     */
    public function muros()
    {
        return $this->hasMany(Muro::class, 'ID_Usuario', 'ID');
    }

    /**
     * Obtener las intervenciones publicadas por este docente.
     */
    public function intervenciones()
    {
        return $this->hasMany(MuroDetalle::class, 'ID_Usuario', 'ID')
            ->where('Tipo_Usuario', 'D');
    }

    /**
     * Obtener el nombre completo concatenado.
     */
    public function getNombreCompletoAttribute()
    {
        return "{$this->Apellido}, {$this->Nombre}";
    }
}
