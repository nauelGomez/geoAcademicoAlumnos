<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\IntensificationRepository;
use Illuminate\Http\Request;

class IntensificationController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(IntensificationRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request, $studentId)
    {
        try {
            $data = $this->repo->getIntensificationTasks($studentId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'count' => count($data)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar tareas de intensificación',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}
