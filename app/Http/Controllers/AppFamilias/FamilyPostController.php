<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\FamilyPostRepository;
use Illuminate\Http\Request;

class FamilyPostController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(FamilyPostRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index($studentId)
    {
        try {
            $data = $this->repo->getPosts($studentId);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los comunicados.',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    public function show($postId, $studentId)
    {
        try {
            $data = $this->repo->getPostDetails($postId, $studentId);

            if (is_null($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comunicado no encontrado o acceso denegado.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el detalle del comunicado.',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}
