<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\ReportRepository;
use Illuminate\Http\Request;

class ReportController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(ReportRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request, $studentId)
    {
        try {
            // El email se podría obtener del usuario autenticado, aquí lo simulamos
            $familyEmail = $request->header('X-Family-Email', ''); 
            $data = $this->repo->getStudentReports($studentId, $familyEmail);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar informes pedagógicos',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}
