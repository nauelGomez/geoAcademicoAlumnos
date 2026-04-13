<?php   

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\MessageRepository;
use App\Http\Controllers\Controller;

class MessageController extends Controller
{
    protected $messageRepo;

    /**
     * Inyectamos el repositorio unificado
     */
    public function __construct(MessageRepository $messageRepo)
    {
        $this->messageRepo = $messageRepo;
    }

public function index($studentId)
{
    // Obtenemos el ID de la institución desde el header o el atributo que setea tu middleware
    $institutionId = request()->header('X-Institution-ID'); 
    
    $familiaId = session('id_usuario') ?: request('id_familia');

    // Fallback con validación doble: Alumno + Institución
    if (!$familiaId) {
        $familiaId = $this->messageRepo->getFamilyIdFromAsociacion($studentId, $institutionId);
    }

    if (!$familiaId) {
        return response()->json([
            'success' => false,
            'message' => "No se encontró asociación para el alumno $studentId en la institución $institutionId."
        ], 404);
    }

    $data = $this->messageRepo->getConversations($studentId, $familiaId);

    return response()->json([
        'success' => true,
        'data' => $data,
        'context' => [
            'id_familia' => $familiaId,
            'id_institucion' => $institutionId
        ]
    ]);
}

    /**
     * Mapea con: GET /messages/recipients/{studentId}
     * ESTE ES EL MÉTODO QUE TE FALTABA
     */
    public function recipients($studentId)
    {
        try {
            $data = $this->messageRepo->getAvailableRecipients($studentId);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Destinatarios disponibles recuperados.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener destinatarios.',
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    /**
     * Mapea con: GET /messages/chat/{codigo}
     */
    public function show($codigo)
    {
        $studentId = request('studentId'); // O pasarlo por parámetro si ajustás la ruta
        $familiaId = session('id_usuario');
        
        $data = $this->messageRepo->getChatDetails($codigo, $studentId, $familiaId);

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Detalle del chat recuperado.'
        ]);
    }

   public function store(Request $request)
{
    $data = $request->all();
    $studentId = $request->input('id_alumno');
    $institutionId = $request->header('X-Institution-ID');

    // 1. Intentamos obtener la familia (Sesión -> Request -> Fallback associations)
    $familiaId = session('id_usuario') ?: $request->input('id_familia');

    if (!$familiaId && $studentId && $institutionId) {
        // Usamos el método que ya creamos en el repo para buscar en la DB general
        $familiaId = $this->messageRepo->getFamilyIdFromAsociacion($studentId, $institutionId);
    }

    // 2. Si sigue vacío, devolvemos error antes de que explote la SQL
    if (!$familiaId) {
        return response()->json([
            'success' => false, 
            'message' => 'Error: No se pudo determinar el ID de Familia para el envío.'
        ], 422);
    }

    // 3. Inyectamos el ID detectado en el array de datos
    $data['id_familia'] = $familiaId;

    try {
        $result = $this->messageRepo->sendMessage($data);

        return response()->json([
            'success' => true,
            'data' => [
                'codigo' => $result,
                'id_familia_usado' => $familiaId
            ],
            'message' => 'Mensaje enviado con éxito.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al procesar el envío en el repositorio.',
            'errors' => [$e->getMessage()]
        ], 500);
    }
}
}