<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\ExamBoardRepository;
use Illuminate\Http\Request;

class ExamBoardController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(ExamBoardRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request, $studentId)
    {
        try {
            $data = $this->repo->getStudentExamData($studentId);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar mesas de examen',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}
