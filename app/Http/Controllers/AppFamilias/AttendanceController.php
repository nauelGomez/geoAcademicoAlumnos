<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\AttendanceRepository;
use Illuminate\Http\Request;

class AttendanceController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(AttendanceRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request, $studentId)
    {
        try {
            $data = $this->repo->getAttendanceDetail($studentId);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el detalle de asistencias',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}
