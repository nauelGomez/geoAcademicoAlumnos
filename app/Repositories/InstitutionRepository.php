<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class InstitutionRepository
{
    // OJO ACÁ: Usamos la conexión principal 'mysql' (o 'familias' si así la definiste en tu database.php)
    protected $connection = 'mysql';

    public function getAll()
    {
        return DB::connection($this->connection)
            ->table('instituciones')
            ->select(['ID', 'Institucion', 'Carpeta', 'Direccion', 'Telefono', 'Mail Reponsable', 'URL', 'Logo'])
            ->get();
    }

    public function getById(int $institutionId)
    {
        return DB::connection($this->connection)
            ->table('instituciones')
            ->select(['ID', 'Institucion', 'Carpeta', 'Direccion', 'Telefono', 'Mail Reponsable', 'URL', 'Logo'])
            ->where('ID', $institutionId)
            ->first();
    }
}