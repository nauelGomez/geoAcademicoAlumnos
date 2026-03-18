<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\MessagingRepository;
use Illuminate\Http\Request;

class MessagingController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(MessagingRepository $repo) {
        $this->repo = $repo;
    }

    public function index(Request $request, $studentId) {
        $familyId = $request->header('X-Family-ID');
        $data = $this->repo->getConversations($studentId, $familyId);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function show(Request $request, $code) {
        $familyId = $request->header('X-Family-ID');
        $data = $this->repo->getMessages($code, $familyId);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function recipients($studentId) {
        $data = $this->repo->getAvailableRecipients($studentId);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function store(Request $request) {
        $request->validate(['mensaje' => 'required', 'id_destinatario' => 'required']);
        $data = $request->all();
        $data['id_familia'] = $request->header('X-Family-ID');
        
        $codigo = $this->repo->sendMessage($data);
        return response()->json(['success' => true, 'codigo' => $codigo]);
    }
}
