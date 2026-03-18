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
}
