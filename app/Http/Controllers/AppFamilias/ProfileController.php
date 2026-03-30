<?php

namespace App\Http\Controllers\AppFamilias;

use App\Http\Controllers\BaseInstitutionController;
use App\Repositories\AppFamilias\ProfileRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(ProfileRepository $repo) {
        $this->repo = $repo;
    }

    public function show($studentId)
    {
        $data = $this->repo->getProfileData($studentId);
        if (!$data) return response()->json(['success' => false, 'message' => 'Alumno no encontrado'], 404);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request, $studentId)
    {
        $request->validate([
            'direccion' => 'required',
            'telefono'  => 'required',
            'password'  => 'nullable|min:4|confirmed'
        ]);

        // CAPTURA DEL ID DESDE EL HEADER
        $familyId = $request->header('X-Family-ID');

        // Validación de seguridad: si no hay ID de familia, no seguimos
        if (!$familyId) {
            return response()->json([
                'success' => false, 
                'message' => 'No se proporcionó ID de Familia en los Headers (X-Family-ID)'
            ], 400);
        }

        $this->repo->updateProfile($studentId, $familyId, $request->all());

        return response()->json(['success' => true, 'message' => 'Datos actualizados con éxito']);
    }

    public function updatePhoto(Request $request, $studentId)
    {
        if (!$request->hasFile('file')) return response()->json(['success' => false, 'message' => 'No file'], 400);

        $file = $request->file('file');
        $prefijo = substr(md5(uniqid(rand())), 0, 6);
        $cleanName = str_replace(['%', "'", " "], "_", $file->getClientOriginalName());
        $fileName = $prefijo . '_' . $cleanName;

        $inst = DB::table('institucion')->first();
        $rutaFTP = $inst->Carpeta . '/imagenes/usuarios';

        // Lógica FTP legacy
        $conn_id = @ftp_connect("pesge.com.ar", 21);
        if ($conn_id && @ftp_login($conn_id, "djmaster@pesge.com.ar", "DiegoLola2016")) {
            ftp_pasv($conn_id, true);
            if (@ftp_chdir($conn_id, $rutaFTP)) {
                if (@ftp_put($conn_id, $fileName, $file->getRealPath(), FTP_BINARY)) {
                    $this->repo->updatePhotoRecord($studentId, $fileName);
                    ftp_close($conn_id);
                    return response()->json(['success' => true, 'message' => 'Foto actualizada']);
                }
            }
        }
        return response()->json(['success' => false, 'message' => 'Error al subir archivo via FTP'], 500);
    }
}
