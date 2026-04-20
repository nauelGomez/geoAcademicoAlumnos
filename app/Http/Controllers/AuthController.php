<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Repositories\AuthRepository;
use Illuminate\Support\Facades\Log;
use Exception;

class AuthController extends Controller
{
    protected $authRepo;

    public function __construct(AuthRepository $authRepo)
    {
        $this->authRepo = $authRepo;
    }

    /**
     * Mapea con: POST /api/auth/family-login
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'codigo_aleatorio' => 'required|string',
            ]);

            $email = $request->input('email');
            $aleatorio = $request->input('codigo_aleatorio');
            
            // Reutilizamos el header que ya manejás en la app
            $institutionId = $request->header('X-Institution-ID');

            if (!$institutionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Falta el ID de Institución en la cabecera.',
                ], 400);
            }

            // Delegamos toda la lógica al repositorio
            $familiaId = $this->authRepo->validateAndGetFamilyId($email, $aleatorio, $institutionId);

            if (!$familiaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales inválidas. Verifique el mail y el código del alumno.',
                ], 401);
            }

            // Generar token
            $token = $this->authRepo->generateTokenForFamily($familiaId);

            return response()->json([
                'success' => true,
                'message' => 'Autenticación exitosa.',
                'data' => [
                    'access_token' => $token,
                    'familia_id' => $familiaId,
                    'institution_id' => $institutionId,
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Error en family login: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno al intentar iniciar sesión.',
                'debug_error' => $e->getMessage(),
            ], 500);
        }
    }
}   