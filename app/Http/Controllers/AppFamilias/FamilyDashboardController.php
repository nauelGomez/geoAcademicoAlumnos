<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\FamilyDashboardRepository;
use Illuminate\Http\Request;

class FamilyDashboardController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(FamilyDashboardRepository $repo)
    {
        $this->repo = $repo;
    }

    public function show(Request $request, $studentId)
    {
        try {
            // studentId es 413 (ID real del alumno en la institución)
            $data = $this->repo->getDashboardData($studentId);

            if (empty($data)) {
                return response()->json(['success' => false, 'message' => 'Alumno no encontrado'], 404);
            }

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Error en el Dashboard',
                'error_debug' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
