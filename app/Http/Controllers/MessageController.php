<?php

namespace App\Http\Controllers;

use App\Repositories\MessageRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    /** @var MessageRepository */
    protected $repo;

    public function __construct(MessageRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Mapea con: GET /api/messages/conversations/{studentId}
     */
    public function index(Request $request, $studentId): JsonResponse
    {
        try {
            $institutionId = $request->header('X-Institution-ID');
            $familiaId = session('id_usuario') ?: $request->input('familia_id');

            // Autodescubrimiento de familia para el Frontend (Astro)
            if (!$familiaId && $studentId && $institutionId) {
                $familiaId = $this->repo->getFamilyIdFromAsociacion($studentId, $institutionId);
            }

            if (!$familiaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo determinar el ID de Familia.',
                ], 403);
            }

            $data = $this->repo->getConversations($studentId, $familiaId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'institution_id' => $institutionId,
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching conversations: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener conversaciones.',
                'debug_error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mapea con: GET /api/messages/recipients/{studentId}
     */
    public function recipients(Request $request, $studentId): JsonResponse
    {
        try {
            $data = $this->repo->getAvailableRecipients($studentId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'institution_id' => $request->header('X-Institution-ID'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching recipients: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener destinatarios.',
                'debug_error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mapea con: GET /api/messages/chat/{codigo}
     * Nota: studentId viene por query string (?studentId=123)
     */
    public function show(Request $request, $codigo): JsonResponse
    {
        try {
            $studentId = $request->input('studentId');
            $institutionId = $request->header('X-Institution-ID');
            $familiaId = session('id_usuario') ?: $request->input('familia_id');

            // Autodescubrimiento de familia
            if (!$familiaId && $studentId && $institutionId) {
                $familiaId = $this->repo->getFamilyIdFromAsociacion($studentId, $institutionId);
            }

            if (!$familiaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo determinar el ID de Familia para leer este chat.',
                ], 403);
            }

            $data = $this->repo->getChatDetails($codigo, $studentId, $familiaId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'institution_id' => $institutionId,
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching chat details: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el chat.',
                'debug_error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mapea con: POST /api/messages/send
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $studentId = $request->input('id_alumno');
            $institutionId = $request->header('X-Institution-ID');
            $familiaId = session('id_usuario') ?: $request->input('familia_id');

            if (!$familiaId && $studentId && $institutionId) {
                $familiaId = $this->repo->getFamilyIdFromAsociacion($studentId, $institutionId);
            }

            if (!$familiaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo determinar el ID de Familia para el envío.',
                ], 422);
            }

            $request->validate([
                'id_destinatario' => 'required',
                'mensaje' => 'required|string|max:5000',
            ]);

            // Inyectamos el familiaId resuelto en los datos que van al repo
            $payload = $request->all();
            $payload['id_familia'] = $familiaId;

            $data = $this->repo->sendMessage($payload);

            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado correctamente.',
                'data' => ['codigo' => $data],
                'institution_id' => $institutionId,
            ], 201);
        } catch (Exception $e) {
            Log::error('Error storing message: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar mensaje.',
                'debug_error' => $e->getMessage(),
            ], 500);
        }
    }
}