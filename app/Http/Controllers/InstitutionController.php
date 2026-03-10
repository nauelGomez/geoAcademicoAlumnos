<?php

namespace App\Http\Controllers;

use App\Repositories\InstitutionRepository;
use Illuminate\Http\JsonResponse;
use Exception;

class InstitutionController extends Controller
{
    /** @var InstitutionRepository */
    private $institutionRepository;

    public function __construct(InstitutionRepository $institutionRepository)
    {
        $this->institutionRepository = $institutionRepository;
    }

    public function index(): JsonResponse
    {
        try {
            $institutions = $this->institutionRepository->getAll();
            
            return response()->json([
                'status' => 'success',
                'data' => $institutions,
                'count' => count($institutions)
            ], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching institutions: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error al obtener instituciones'], 500);
        }
    }

    public function show(int $institutionId): JsonResponse
    {
        try {
            $institution = $this->institutionRepository->getById($institutionId);
            
            if (!$institution) {
                return response()->json(['status' => 'error', 'message' => 'Institución no encontrada'], 404);
            }

            return response()->json(['status' => 'success', 'data' => $institution], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching institution: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error al obtener institución'], 500);
        }
    }
}