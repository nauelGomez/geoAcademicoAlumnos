<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $table = 'materias'; // Nombre exacto en tu BD
    protected $primaryKey = 'ID';  // Usas ID en mayúsculas según tu código
    public $timestamps = false;    // PHP puro no suele usarlos por defecto

    protected $fillable = [
        'Materia', 
        'ID_Curso'
    ];
}