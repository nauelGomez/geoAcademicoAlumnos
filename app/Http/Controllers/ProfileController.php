<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Repositories\ProfileRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /** @var ProfileRepository */
    protected $repo;

    public function __construct(ProfileRepository $repo)
    {
        $this->repo = $repo;
    }

    public function show(Request $request, $studentId): JsonResponse
    {
        try {
            $data = $this->repo->getProfile($studentId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'institution_id' => $request->header('X-Institution-ID'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Error fetching profile: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener perfil.',
                'debug_error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateProfileRequest $request, $studentId): JsonResponse
    {
        try {
            $updatedData = $this->repo->updateProfile($studentId, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado correctamente.',
                'data' => $updatedData,
                'institution_id' => $request->header('X-Institution-ID'),
            ], 200);
        } catch (Exception $e) {
            Log::error('Error updating profile: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar perfil.',
                'debug_error' => $e->getMessage(),
            ], 500);
        }
    }
}
