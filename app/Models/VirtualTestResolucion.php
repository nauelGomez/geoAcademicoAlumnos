<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualTestResolucion extends Model
{
    protected $table = 'tareas_test_resoluciones';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = ['ID_Tarea', 'ID_Alumno', 'Correcion', 'Resolucion', 'Comentario_Correccion'];
}
