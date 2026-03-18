<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualTestEnvio extends Model
{
    protected $table = 'tareas_test_envios';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = ['ID_Tarea', 'ID_Destinatario', 'Leido', 'Envio', 'MailD'];
}