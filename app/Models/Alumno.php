<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alumno extends Model
{
    protected $table = 'alumnos';
    
    // ESTO ES CLAVE para evitar que busque "id" en minúscula
    protected $primaryKey = 'ID'; 
    public $timestamps = false;

    protected $fillable = ['Apellido', 'Nombre', 'ID_Curso', 'ID_Nivel', 'ID_Situacion'];

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'ID_Curso', 'ID'); // Asegurar FK y PK correctas
    }

    public function notasCursada()
    {
        return $this->hasMany(NotaCursada::class, 'ID_Alumno');
    }
}