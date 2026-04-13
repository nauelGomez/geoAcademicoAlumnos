<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $table = 'chat';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $fillable = [
        'Fecha', 'Hora', 'ID_Remitente', 'Tipo_Remitente', 'ID_Destinatario', 
        'Tipo_Destinatario', 'Mensaje', 'Codigo', 'ID_Alumno', 'ID_Nivel', 'P',
        'Leido', 'Fecha_Leido', 'Hora_Leido'
    ];
}