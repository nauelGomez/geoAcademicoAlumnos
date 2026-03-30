<?php

namespace App\Http\Controllers;

use App\Repositories\CertificateRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CertificateController extends Controller
{
    /** @var CertificateRepository */
    protected $repo;

    public function __construct(CertificateRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request, $studentId): JsonResponse
    {
        try {
            $data = $this->repo->getStudentRequests($studentId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'institution_id' => $request->header('X-Institution-ID'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching certificates: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener certificados.',
                'debug_error' => $e->getMessage(),
            ], 500);
        }
    }

    public function create(Request $request, $studentId): JsonResponse
    {
        try {
            $data = $this->repo->getFormData($studentId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'institution_id' => $request->header('X-Institution-ID'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching certificate form data: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del formulario de certificados.',
                'debug_error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request, $studentId): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_certificado' => 'required|integer|min:1',
                'destinatario' => 'required|string|max:255',
                'detalle' => 'nullable|string',
                'id_m' => 'nullable|integer',
            ]);

            $nuevaSolicitud = $this->repo->storeRequest($studentId, $validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud enviada correctamente.',
                'data' => $nuevaSolicitud,
            ], 201);
        } catch (Exception $e) {
            Log::error('Error storing certificate request: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la solicitud de certificado.',
                'debug_error' => $e->getMessage(),
            ], 500);
        }
    }
}
