<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\AgendaRepository;
use Illuminate\Http\Request;

class AgendaController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(AgendaRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request, $studentId)
    {
        try {
            $data = $this->repo->getStudentAgenda($studentId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'count' => count($data)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la agenda',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}
