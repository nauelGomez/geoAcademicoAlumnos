<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateRequest extends Model
{
    protected $connection = 'tenant';

    protected $table = 'solicitudes_certificados';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Alumno',
        'ID_Ciclo_Lectivo',
        'ID_Certificado',
        'Fecha',
        'Detalle',
        'Destino',
        'Estado',
        'B',
        'Aleatorio',
    ];

    public function tipo()
    {
        return $this->belongsTo(CertificateType::class, 'ID_Certificado', 'ID');
    }
}
