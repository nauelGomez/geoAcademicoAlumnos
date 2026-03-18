<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\AnnouncementRepository;
use Illuminate\Http\Request;

class AnnouncementController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(AnnouncementRepository $repo) {
        $this->repo = $repo;
    }

    public function index(Request $request, $studentId) {
        $email = $request->header('X-Family-Email');
        $data = $this->repo->getStudentAnnouncements($studentId, $email);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function show(Request $request, $tipo, $code, $studentId) // <-- ORDEN CORRECTO SEGÚN LA RUTA
{
    try {
        // Necesitamos el email para validar que este comunicado es de ESTA familia
        $email = $request->header('X-Family-Email');
        $familyId = $request->header('X-Family-ID');

        // Pasamos el email al repositorio para buscar el registro exacto
        $data = $this->repo->getAnnouncementDetail($studentId, $tipo, $code, $familyId, $email);

        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Comunicado no encontrado o acceso denegado'], 404);
        }

        return response()->json(['success' => true, 'data' => $data]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al abrir el comunicado',
            'debug' => $e->getMessage()
        ], 500);
    }
}
}
