<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    protected $connection = 'tenant';

    protected $table = 'chat_codigo_conversaciones';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = ['Codigo', 'ID_Familia', 'ID_Alumno', 'ID_Docente', 'Fecha', 'Hora'];
}
