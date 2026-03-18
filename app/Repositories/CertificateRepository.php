<?php

namespace App\Repositories;

use App\Models\Alumno;
use App\Models\CertificateRequest;
use App\Models\CertificateType;
use App\Models\ExamInscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CertificateRepository
{
    public function getStudentRequests($studentId)
    {
        $alumno = Alumno::findOrFail($studentId);

        $cicloId = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->value('ID');

        $habilitado = DB::table('nivel_parametros')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->value('HabCert');

        $carpetaInst = DB::table('institucion')->where('ID', 1)->value('Carpeta');
        $enlaceBase = $carpetaInst ? "https://geoeducacion.com.ar/{$carpetaInst}" : "";

        $solicitudesQuery = CertificateRequest::with('tipo')
            ->where('ID_Alumno', $alumno->ID)
            ->where('B', 0)
            ->orderBy('ID', 'desc');

        if ($cicloId) {
            $solicitudesQuery->where('ID_Ciclo_Lectivo', $cicloId);
        }

        $solicitudes = $solicitudesQuery->get()->map(function ($sol) use ($enlaceBase) {
            return $this->formatRequest($sol, $enlaceBase);
        });

        return [
            'habilitado' => ($habilitado == 1),
            'solicitudes' => $solicitudes,
        ];
    }

    private function formatRequest($sol, $enlaceBase)
    {
        $estadoText = 'Sin Respuesta';
        $puedeCancelar = false;
        $urlPdf = null;

        if ($sol->Estado == 0) {
            $estadoText = 'Sin Respuesta';
            $puedeCancelar = true;
        } elseif ($sol->Estado == 1) {
            $estadoText = 'Aprobado';
            $urlPdf = "{$enlaceBase}/reportes/api_certificado.php?type=2&code={$sol->Aleatorio}";
        } elseif ($sol->Estado == 2) {
            $estadoText = 'Denegado';
        }

        $fecha = '';
        try {
            $fecha = Carbon::parse($sol->Fecha)->format('d/m/Y');
        } catch (\Exception $e) {
            $fecha = '';
        }

        return [
            'id' => $sol->ID,
            'fecha' => $fecha,
            'certificado' => $sol->tipo->Certificado ?? 'Desconocido',
            'detalle' => $sol->Detalle,
            'destino' => $sol->Destino,
            'estado' => $estadoText,
            'puede_cancelar' => $puedeCancelar,
            'archivo_url' => $urlPdf,
        ];
    }

    public function getFormData($studentId)
    {
        $alumno = Alumno::findOrFail($studentId);

        $cicloId = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->value('ID');

        $tipos = CertificateType::where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Auto', 1)
            ->orderBy('Certificado')
            ->get(['ID as id', 'Certificado as nombre']);

        $mesasQuery = ExamInscription::with(['mesa.titular', 'mesa.materia'])
            ->where('ID_Alumno', $alumno->ID)
            ->where('Estado', 'C')
            ->where('B', 0);

        if ($cicloId) {
            $mesasQuery->where('ID_Ciclo_Lectivo', $cicloId);
        }

        $mesas = $mesasQuery->get()
            ->map(function ($ins) {
                $mesa = $ins->mesa;
                if (!$mesa) {
                    return null;
                }

                $fechaStr = '';
                try {
                    $fechaStr = Carbon::parse($mesa->Fecha)->format('d/m/Y');
                } catch (\Exception $e) {
                    $fechaStr = '';
                }

                $materia = $mesa->materia->Materia ?? '';
                $apellido = $mesa->titular->Apellido ?? '';
                $hora = $mesa->Hora ?? '';

                return [
                    'id_mesa' => $mesa->ID,
                    'descripcion' => "{$materia} - {$fechaStr} {$hora} (Prof. {$apellido})",
                ];
            })
            ->filter()
            ->values();

        return [
            'alumno_id' => $alumno->ID,
            'ciclo_id' => $cicloId,
            'tipos_certificados' => $tipos,
            'mesas_examen' => $mesas,
        ];
    }

    public function storeRequest($studentId, $data)
    {
        $alumno = Alumno::findOrFail($studentId);

        $cicloId = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->value('ID');

        $aleatorio = substr(md5(uniqid((string)rand(), true)), 0, 15);

        $detalleFinal = isset($data['detalle']) ? (string)$data['detalle'] : '';
        if ((int)$data['id_certificado'] === 3 && !empty($data['id_m'])) {
            $detalleFinal .= ' (Ref. Mesa Examen Nro: ' . $data['id_m'] . ')';
        }

        return CertificateRequest::create([
            'ID_Alumno' => $alumno->ID,
            'ID_Ciclo_Lectivo' => $cicloId,
            'ID_Certificado' => $data['id_certificado'],
            'Fecha' => date('Y-m-d'),
            'Detalle' => trim($detalleFinal),
            'Destino' => $data['destinatario'],
            'Estado' => 0,
            'B' => 0,
            'Aleatorio' => $aleatorio,
        ]);
    }
}
