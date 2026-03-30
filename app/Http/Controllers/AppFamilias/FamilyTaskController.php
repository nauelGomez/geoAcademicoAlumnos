<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\FamilyTaskRepository;
use Illuminate\Http\Request;

class FamilyTaskController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(FamilyTaskRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index($studentId)
    {
        return response()->json(['success' => true, 'data' => $this->repo->getStudentTasks($studentId)]);
    }

    public function show($studentId, $taskId)
    {
        $data = $this->repo->getTaskDetail($taskId, $studentId);
        if (!$data) {
            return response()->json([
                'success' => false, 
                'message' => 'Tarea no encontrada o no tienes acceso a ella'
            ], 404);
        }
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function storeResolution(Request $request, $studentId, $taskId)
    {
        $this->validate($request, ['contenido' => 'required|string']);
        $data = $this->repo->storeResolution($taskId, $studentId, $request->contenido);
        return response()->json(['success' => true, 'message' => 'Resolución enviada', 'data' => $data]);
    }

    public function storeQuery(Request $request, $studentId, $taskId)
    {
        $this->validate($request, ['consulta' => 'required|string']);
        $data = $this->repo->storeQuery($taskId, $studentId, $request->consulta);
        return response()->json(['success' => true, 'message' => 'Consulta enviada', 'data' => $data]);
    }
}
