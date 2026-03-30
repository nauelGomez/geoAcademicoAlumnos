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

    public function index(Request $request, $studentId): JsonResponse
    {
        try {
            $familiaId = $request->input('familia_id', null);
            $data = $this->repo->getConversations($studentId, $familiaId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'institution_id' => $request->header('X-Institution-ID'),
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

    public function create(Request $request, $studentId): JsonResponse
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

    public function show(Request $request, $studentId, $codigo): JsonResponse
    {
        try {
            $familiaId = $request->input('familia_id', 1);
            $data = $this->repo->getChatDetails($codigo, $studentId, $familiaId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'institution_id' => $request->header('X-Institution-ID'),
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

    public function store(Request $request, $studentId): JsonResponse
    {
        try {
            $familiaId = $request->input('familia_id', 1);

            $request->validate([
                'destinatario' => 'required|integer|min:1',
                'mensaje' => 'required|string|max:5000',
            ]);

            $data = $this->repo->startConversation($studentId, $familiaId, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Mensaje enviado correctamente.',
                'data' => $data,
                'institution_id' => $request->header('X-Institution-ID'),
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

    public function reply(Request $request, $studentId, $codigo): JsonResponse
    {
        try {
            $familiaId = $request->input('familia_id', 1);

            $request->validate([
                'mensaje' => 'required|string|max:5000',
            ]);

            $data = $this->repo->replyMessage($codigo, $studentId, $familiaId, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Respuesta enviada correctamente.',
                'data' => $data,
                'institution_id' => $request->header('X-Institution-ID'),
            ], 201);
        } catch (Exception $e) {
            Log::error('Error replying message: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al responder mensaje.',
                'debug_error' => $e->getMessage(),
            ], 500);
        }
    }
}
