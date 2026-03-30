<?php

namespace App\Http\Controllers;

use App\Repositories\PostRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    /** @var PostRepository */
    protected $repo;

    public function __construct(PostRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request, $studentId): JsonResponse
    {
        try {
            $data = $this->repo->getPublications($studentId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Publicaciones cargadas con éxito.',
                'institution_id' => $request->header('X-Institution-ID'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching posts: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener publicaciones.',
                'debug_error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $postId, $studentId): JsonResponse
    {
        try {
            $data = $this->repo->getPublicationDetails($postId, $studentId);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Publicación no encontrada o acceso denegado.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Detalle de la publicación cargado con éxito.',
                'institution_id' => $request->header('X-Institution-ID'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching post detail: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el detalle de la publicación.',
                'debug_error' => $e->getMessage(),
            ], 500);
        }
    }

    public function markAsRead(Request $request, $postId, $studentId): JsonResponse
    {
        try {
            $marked = $this->repo->markAsRead($postId, $studentId);

            if (!$marked) {
                return response()->json([
                    'success' => false,
                    'message' => 'El comunicado no existe o ya fue marcado como leído.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Comunicado marcado como leído exitosamente.'
            ], 200);

        } catch (Exception $e) {
            Log::error('Error marking post as read: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al marcar la publicación como leída.',
                'debug_error' => $e->getMessage(),
            ], 500);
        }
    }
}
