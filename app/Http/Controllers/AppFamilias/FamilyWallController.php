<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\FamilyWallRepository;
use Illuminate\Http\Request;

class FamilyWallController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(FamilyWallRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request, $studentId)
    {
        try {
            $data = $this->repo->getWalls($studentId);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los muros de novedades',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}
