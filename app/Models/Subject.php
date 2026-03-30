<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    // Tabla legacy: materias
    protected $table = 'materias';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = ['Materia', 'ID_Curso', 'ID_Personal'];
}