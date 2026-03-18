<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repositories\VirtualTestRepository;
use Illuminate\Http\Request;
use App\Models\Alumno;

class VirtualTestController extends Controller
{
    protected $repository;

    public function __construct(VirtualTestRepository $repository)
    {
        $this->repository = $repository;
    }

public function index($studentId)
{
    // 1. Verificamos si la tabla existe y cuántos registros tiene
    $count = \DB::table('alumnos')->count();
    
    // 2. Intentamos buscar el alumno de forma manual para ver qué trae
    $rawAlumno = \DB::table('alumnos')->where('ID', $studentId)->first();

    if (!$rawAlumno) {
        return response()->json([
            'error' => "El alumno con ID {$studentId} NO existe en la base de datos.",
            'debug' => [
                'tabla' => 'alumnos',
                'total_registros_en_tabla' => $count,
                'id_buscado' => $studentId,
                'base_datos' => \DB::connection()->getDatabaseName()
            ]
        ], 404);
    }

    // Si llega acá, el alumno existe en la DB, pero quizás falla Eloquent
    try {
        $alumno = Alumno::findOrFail($studentId);
        $tests = $this->repository->getAvailableForAlumno($alumno);

        return response()->json([
            'success' => true,
            'data' => $tests
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Error en Eloquent o Repositorio',
            'mensaje' => $e->getMessage()
        ], 500);
    }
}
}