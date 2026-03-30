<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\FamilyStudentRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class FamilyStudentController extends BaseInstitutionController
{
    /** @var FamilyStudentRepository */
    protected $repo;

    public function __construct(FamilyStudentRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $familiaId = $request->input('familia_id', 11834);
            $institucionId = $request->header('X-Institution-ID');

            if (!$institucionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Falta header X-Institution-ID',
                ], 400);
            }

            // --- FORZAMOS LA CONEXIÓN CORRECTA ---
            $dbConfig = config("institutions.{$institucionId}");
            
            if ($dbConfig) {
                Config::set('database.connections.mysql.host', $dbConfig['host']);
                Config::set('database.connections.mysql.database', $dbConfig['database']);
                Config::set('database.connections.mysql.username', $dbConfig['username']);
                Config::set('database.connections.mysql.password', $dbConfig['password']);
                
                DB::purge('mysql'); // Limpiamos la conexión vieja
                DB::reconnect('mysql'); // Conectamos a la del colegio (ej: 34)
            }

            $data = $this->repo->getLinkedStudents($familiaId, $institucionId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'institution_id' => $institucionId
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}
