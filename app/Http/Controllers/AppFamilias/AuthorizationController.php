<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\AuthorizationRepository;
use Illuminate\Http\Request;

class AuthorizationController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(AuthorizationRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request, $studentId)
    {
        $familyId = $request->header('X-Family-ID');
        $data = $this->repo->getData($studentId, $familyId);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function storePerson(Request $request, $studentId)
    {
        $data = $request->all();
        $data['student_id'] = $studentId;
        $data['family_id'] = $request->header('X-Family-ID');
        
        $id = $this->repo->storePerson($data);
        return response()->json(['success' => true, 'id' => $id, 'message' => 'Persona autorizada guardada']);
    }

    public function storeNotice(Request $request, $studentId)
    {
        $data = $request->all();
        $data['student_id'] = $studentId;
        $data['family_id'] = $request->header('X-Family-ID');
        
        $id = $this->repo->storeNotice($data);
        return response()->json(['success' => true, 'id' => $id, 'message' => 'Aviso de retiro creado']);
    }

    public function destroyPerson(Request $request, $id)
    {
        $familyId = $request->header('X-Family-ID');
        $this->repo->deletePerson($id, $familyId);
        return response()->json(['success' => true, 'message' => 'Autorización eliminada']);
    }

    public function destroyNotice(Request $request, $id)
    {
        $familyId = $request->header('X-Family-ID');
        $this->repo->deleteNotice($id, $familyId);
        return response()->json(['success' => true, 'message' => 'Aviso de retiro eliminado']);
    }
}
