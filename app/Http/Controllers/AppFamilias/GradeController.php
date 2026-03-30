<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\GradeRepository;
use Illuminate\Http\Request;

class GradeController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(GradeRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request, $studentId)
    {
        try {
            $data = $this->repo->getStudentGrades($studentId);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener calificaciones',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}
