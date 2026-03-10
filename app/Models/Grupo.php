<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    protected $table = 'grupos';
    protected $fillable = ['ID_Alumno', 'ID_Materia_Grupal', 'ID_Ciclo_Lectivo'];
    public $timestamps = false; // Ajustar si usa timestamps
}