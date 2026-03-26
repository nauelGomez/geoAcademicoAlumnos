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

    /**
     * Listado de mesas de examen para el alumno.
     *
     * @param int $studentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($studentId)
    {
        try {
            $data = $this->repo->getExamBoards($studentId);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las mesas de examen.',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}
