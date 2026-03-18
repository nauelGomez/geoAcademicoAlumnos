<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\EnrollmentRepository;
use Illuminate\Http\Request;

class EnrollmentController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(EnrollmentRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request, $studentId)
    {
        try {
            $data = $this->repo->getAvailableGroups($studentId);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar grupos disponibles',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request, $studentId)
    {
        $request->validate([
            'id_materia' => 'required|integer'
        ]);

        try {
            $this->repo->enrollStudent($studentId, $request->id_materia);
            return response()->json(['success' => true, 'message' => 'Inscripción realizada con éxito']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
