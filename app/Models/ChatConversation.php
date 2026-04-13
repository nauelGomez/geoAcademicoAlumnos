<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    protected $table = 'chat_codigo_conversaciones';
    protected $primaryKey = 'ID';
    public $timestamps = false; // El legacy usa Fecha/Hora manual
    protected $fillable = ['ID_Docente', 'ID_Familia', 'ID_Alumno', 'Codigo', 'Fecha', 'Hora'];
}