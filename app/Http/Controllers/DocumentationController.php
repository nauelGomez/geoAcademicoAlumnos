<?php

namespace App\Http\Controllers;

use App\Repositories\DocumentationRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DocumentationController extends Controller
{
    protected $docRepo;

    public function __construct(DocumentationRepository $docRepo)
    {
        $this->docRepo = $docRepo;
    }

    public function studentDocumentation(Request $request, $studentId): JsonResponse
    {
        try {
            // Recibimos el mail del responsable por parámetro GET (ej: ?mail=padre@mail.com)
            $email = $request->query('mail', '');
            
            $data = $this->docRepo->getDocumentation((int)$studentId, $email);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el alumno.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $data,
                'message' => 'Documentación cargada correctamente.'
            ], 200);

        } catch (Exception $e) {
            Log::error('Error cargando documentación: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al cargar la documentación.',
                'error'   => env('APP_DEBUG') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }
}