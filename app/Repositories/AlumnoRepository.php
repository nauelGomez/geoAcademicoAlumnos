<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    // Nombre de la tabla según tu DB local
    protected $table = 'alumnos'; 

    // En tu imagen, ID está en mayúsculas [cite: 11]
    protected $primaryKey = 'ID'; 

    // IMPORTANTE: Tu tabla no tiene created_at/updated_at [cite: 12]
    public $timestamps = false; 

    // Campos fillable con los nombres exactos de tu imagen
    protected $fillable = [
        'Nombre', 'Apellido', 'DNI', 'Mail_Reponsable', // Confirmado el typo 'Reponsable'
        'ID_Curso', 'ID_Nivel'
    ];
    public function getAlumnoConMaterias($studentId)
    {
        return Student::on('tenant') // Conexión local de la institución
            ->select('ID', 'Nombre', 'Apellido', 'ID_Curso') // Tabla Alumnos
            ->with(['materias' => function($query) {
                $query->select('ID', 'Materia', 'Curso', 'ID_Plan'); // Tabla materias_planes
            }])
            ->find($studentId);
    }
}