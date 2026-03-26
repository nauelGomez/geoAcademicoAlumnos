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

    /**
     * Listado de informes pedagógicos para el alumno.
     *
     * @param int $studentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($studentId)
    {
        try {
            $data = $this->repo->getReports($studentId);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los informes pedagógicos.',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}
