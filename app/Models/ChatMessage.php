<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $connection = 'tenant';

    protected $table = 'chat';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Codigo',
        'Mensaje',
        'Fecha',
        'Hora',
        'ID_Remitente',
        'Tipo_Remitente',
        'ID_Destinatario',
        'Tipo_Destinatario',
        'Leido',
        'Fecha_Leido',
        'Hora_Leido',
    ];
}
