<?php

namespace App\Repositories;

use App\Models\Alumno;
use App\Models\ExamGrade;
use App\Models\ExamInscription;
use App\Models\ExamPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExamSessionRepository
{
    public function getInscriptions($studentId)
    {
        $alumno = Alumno::findOrFail($studentId);

        $cicloId = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')
            ->value('ID');

        $periodoActivo = null;
        if ($cicloId) {
            $periodoActivo = ExamPeriod::where('Vigente', 'SI')
                ->where('ID_Ciclo_Lectivo', $cicloId)
                ->first();
        }

        $vigentes = collect();
        if ($periodoActivo) {
            $vigentes = ExamInscription::with(['mesa.titular', 'mesa.materia', 'periodo'])
                ->where('ID_Alumno', $alumno->ID)
                ->where('ID_Periodo', $periodoActivo->ID)
                ->where('B', 0)
                ->get()
                ->map(function ($ins) use ($periodoActivo) {
                    return $this->formatInscription($ins, $periodoActivo, true);
                });
        }

        $anteriores = ExamInscription::with(['mesa.titular', 'mesa.materia', 'periodo'])
            ->where('ID_Alumno', $alumno->ID)
            ->where('B', 0);

        if ($periodoActivo) {
            $anteriores->where('ID_Periodo', '!=', $periodoActivo->ID);
        }

        $anteriores = $anteriores->orderBy('ID', 'desc')
            ->get()
            ->map(function ($ins) {
                return $this->formatInscription($ins, null, false);
            });

        return [
            'periodo_nombre' => $periodoActivo->Periodo ?? 'Ninguno',
            'inscripcion_abierta' => (bool)($periodoActivo->Abierta ?? false),
            'vigentes' => $vigentes,
            'anteriores' => $anteriores,
        ];
    }

    // Saqué el "?" del ExamPeriod y el ": array" del final
    private function formatInscription($ins, $periodoActivo, $esVigente)
    {
        $mesa = $ins->mesa;
        $estadoText = ($ins->Estado == 'P') ? 'Pendiente' : 'Confirmada';
        $puedeCancelar = true;

        if ($ins->Estado == 'C' && $mesa) {
            try {
                $fechaMesa = Carbon::parse($mesa->Fecha);
            } catch (\Exception $e) {
                $fechaMesa = null;
            }

            if ($fechaMesa && $periodoActivo) {
                $dias = (int)($periodoActivo->Dias_Baja ?? 0);
                $fechaLimite = $fechaMesa->copy()->subDays($dias);
                $puedeCancelar = Carbon::now()->lt($fechaLimite);
            }

            $apellido = $mesa->titular->Apellido ?? '';
            $fechaStr = $fechaMesa ? $fechaMesa->format('d/m/Y') : '';
            $hora = $mesa->Hora ?? '';

            $estadoText = trim("Confirmada - {$fechaStr} {$hora} (Prof. {$apellido})");
        }

        if (!$esVigente && $mesa && ($mesa->Cierre ?? null) == 'SI') {
            $nota = ExamGrade::where('ID_Mesa', $mesa->ID)
                ->where('ID_Alumno', $ins->ID_Alumno)
                ->first();

            $calificacion = 'S/N';
            $libro = '';
            $folio = '';

            if ($nota) {
                $calificacion = ((int)($nota->Calificacion ?? 0) === 0) ? 'Ausente' : $nota->Calificacion;
                $libro = $nota->Libro ?? '';
                $folio = $nota->Folio ?? '';
            }

            $extra = $nota ? " (Libro: {$libro} Folio: {$folio})" : '';
            $estadoText = "Finalizada - Calif: {$calificacion}{$extra}";
        }

        return [
            'id' => $ins->ID,
            'periodo' => $ins->periodo->Periodo ?? '',
            'materia' => $mesa->materia->Materia ?? 'N/A',
            'estado' => $estadoText,
            'puede_cancelar' => $puedeCancelar,
            'archivo' => $ins->Archivo ?? null,
        ];
    }
}