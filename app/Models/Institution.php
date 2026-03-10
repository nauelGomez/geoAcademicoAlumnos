<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'instituciones';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID',
        'Institucion',
        'Carpeta',
        'ID_Ciudad',
        'ID_Provincia',
        'Direccion',
        'Telefono',
        'CP',
        'Mail',
        'Seguridad',
        'Server',
        'User',
        'Pass',
        'DB_Name',
        'URL',
        'Logo',
        'Ver_App'
    ];

    protected $hidden = [
        'Pass',
        'Seguridad'
    ];

    /**
     * Get the full URL for the institution
     */
    public function getFullUrlAttribute()
    {
        return $this->URL ? rtrim($this->URL, '/') : '';
    }

    /**
     * Get the full logo path
     */
    public function getFullLogoPathAttribute()
    {
        return $this->Logo ? $this->Logo : '';
    }

    /**
     * Check if institution has app access
     */
    public function getHasAppAccessAttribute()
    {
        return $this->Ver_App > 0;
    }

    /**
     * Get formatted address
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([$this->Direccion, $this->CP]);
        return implode(', ', $parts);
    }
}
