<?php

namespace App\Http\Controllers;

use App\Repositories\ExamSessionRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExamSessionController extends Controller
{
    /** @var ExamSessionRepository */
    private $repo;

    public function __construct(ExamSessionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request, $studentId): JsonResponse
    {
        try {
            $data = $this->repo->getInscriptions((int)$studentId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'institution_id' => $request->header('X-Institution-ID'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching exam sessions: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener mesas de examen.',
                'debug_error' => $e->getMessage(),
            ], 500);
        }
    }
}
