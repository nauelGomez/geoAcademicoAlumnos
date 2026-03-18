<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\PostRepository;
use Illuminate\Http\Request;

class PostController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(PostRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request, $studentId)
    {
        try {
            $email = $request->header('X-Family-Email');
            $data = $this->repo->getStudentPosts($studentId, $email);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las publicaciones',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $postId, $studentId)
    {
        try {
            $email = $request->header('X-Family-Email');
            $data = $this->repo->getPostDetail($postId, $studentId, $email);

            if (!$data) {
                return response()->json(['success' => false, 'message' => 'Publicación no encontrada'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al abrir la publicación',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}
