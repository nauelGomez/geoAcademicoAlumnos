<?php

namespace App\Repositories;

use App\Models\Student;

class AlumnoRepository
{
    protected $model;

    public function __construct(Student $student)
    {
        $this->model = $student;
    }

    public function getAlumnoConMaterias($studentId)
    {
        // La lógica de negocio y queries vive aquí, no en el modelo
        return $this->model->newQuery()
            ->select('ID', 'Nombre', 'Apellido', 'ID_Curso')
            ->with(['materias' => function($query) {
                $query->select('ID', 'Materia', 'Curso', 'ID_Plan');
            }])
            ->find($studentId);
    }

    public function getAlumnosPaginados($perPage = 50)
    {
        return $this->model->paginate($perPage);
    }
}