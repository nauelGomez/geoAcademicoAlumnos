<?php

namespace App\Repositories;

use App\Models\Alumno;
use App\Models\MateriaGrupal;
use App\Models\CicloLectivo;
use App\Models\NivelParametro;
use App\Models\PlanEstudioCorrelativa;
use Illuminate\Support\Facades\DB; // <-- ACÁ ESTÁ EL FIX DEL ERROR
use Exception;

class InscripcionRepository
{
    public function getMateriasDisponibles(int $alumnoId)
    {
        $alumno = Alumno::with(['curso.plan', 'notasCursada'])->find($alumnoId);
        
        if (!$alumno) {
            throw new Exception("Alumno no encontrado.");
        }

        $cicloLectivo = CicloLectivo::where('ID_Nivel', $alumno->ID_Nivel)
                                    ->where('Vigente', 'SI')->first();

        $parametros = NivelParametro::where('ID_Nivel', $alumno->ID_Nivel)->first();
        
        $alumnoNoRegular = ($alumno->ID_Situacion != 2);
        $periodoCerrado = (!$parametros || $parametros->HabAI != 1);

        // Materias que el alumno ya aprobó (Final o Cursada)
        $materiasAprobadasIds = $alumno->notasCursada->where('Final', 'SI')->pluck('ID_Materia')->toArray();
        $materiasCursadasIds = $alumno->notasCursada->where('Cursada', 1)->pluck('ID_Materia')->toArray();

        // Unimos ambas para ocultarlas del listado (Como hacía el legacy)
        $materiasOcultasIds = array_unique(array_merge($materiasAprobadasIds, $materiasCursadasIds));

        // Mapeo para el chequeo de correlativas
        $materiasPlanAprobadasFinal = DB::table('materias')
            ->whereIn('ID', $materiasAprobadasIds)
            ->pluck('ID_Materia_Plan')->toArray();

        $materiasPlanAprobadasCursada = DB::table('materias')
            ->whereIn('ID', $materiasCursadasIds)
            ->pluck('ID_Materia_Plan')->toArray();

        // Traemos la oferta de materias, OCULTANDO las que ya aprobó
        $gruposDisponibles = MateriaGrupal::withCount('inscripciones')
            ->join('materias', 'materias.ID', '=', 'materias_grupales.ID_Materia')
            ->join('materias_planes', 'materias_planes.ID', '=', 'materias.ID_Materia_Plan')
            ->join('personal', 'personal.ID', '=', 'materias_grupales.ID_Personal')
            ->where('materias_grupales.ID_Ciclo_Lectivo', optional($cicloLectivo)->ID)
            ->where('materias_grupales.AI', 'SI')
            ->where('materias.Turno', optional($alumno->curso)->Turno)
            ->whereNotIn('materias_grupales.ID_Materia', $materiasOcultasIds) // <-- ESTO LAS OCULTA
            ->select(
                'materias_grupales.*', 
                'materias.Materia as Nombre_Materia',
                'materias.ID_Materia_Plan', 
                'materias.ID as Materia_Instancia_ID', 
                'personal.Apellido as Apellido_Docente'
            )
            ->get();

        $resultado = [];

        foreach ($gruposDisponibles as $grupo) {
            $causales = [];
            $puedeInscribirse = true;

            if ($alumnoNoRegular) {
                $causales[] = 'El alumno no es regular.';
                $puedeInscribirse = false;
            }
            if ($periodoCerrado) {
                $causales[] = 'Período de inscripciones cerrado.';
                $puedeInscribirse = false;
            }

            $cupoDisponible = $grupo->Cupo > 0 ? ($grupo->Cupo - $grupo->inscripciones_count) : 'Sin límite';
            if ($grupo->Cupo > 0 && $cupoDisponible <= 0) {
                $causales[] = 'Grupo Completo';
                $puedeInscribirse = false;
            }

            // Chequeo estricto de Correlativas
            $evaluacionCorrelativas = $this->checkCorrelativas(
                $grupo->ID_Materia_Plan, 
                $materiasPlanAprobadasFinal, 
                $materiasPlanAprobadasCursada
            );
            
            if (!$evaluacionCorrelativas['cumple']) {
                $causales[] = $evaluacionCorrelativas['causal'];
                $puedeInscribirse = false;
            }

            // Solicitud de Excepción (Salvoconducto)
            $solicitudPendiente = DB::table('solicitudes_excepcion_cursada')
                ->where('ID_Materia', $grupo->Materia_Instancia_ID)
                ->where('ID_Alumno', $alumno->ID)
                ->where('ID_Ciclo_Lectivo', optional($cicloLectivo)->ID)
                ->where('B', 0)
                ->exists();

            // Solo permite excepción si está bloqueada POR CORRELATIVAS
            $bloqueoPorCorrelativa = in_array($evaluacionCorrelativas['causal'], $causales);
            $permiteExcepcion = (!$puedeInscribirse && optional($parametros)->HabSE == 1 && !$solicitudPendiente && $bloqueoPorCorrelativa);

            $resultado[] = [
                'id_materia_grupal' => $grupo->ID,
                'materia' => $grupo->Nombre_Materia,
                'docente' => 'Prof. ' . $grupo->Apellido_Docente,
                'cupo_disponible' => $cupoDisponible,
                'permite_inscripcion' => $puedeInscribirse,
                'estado' => $puedeInscribirse ? 'DISPONIBLE' : 'BLOQUEADA',
                'motivos_bloqueo' => implode(" | ", $causales),
                'permite_excepcion' => $permiteExcepcion
            ];
        }

        return [
            'status' => 'success', 
            'data' => [
                'plan' => optional(optional($alumno->curso)->plan)->Nombre ?? 'Sin Plan', 
                'disponibles' => $resultado
            ]
        ];
    }

    /**
     * Función Privada para el Semáforo de Correlativas (Con textos exactos al legacy)
     */
    private function checkCorrelativas(int $idMateriaPlan, array $materiasPlanFinalAprobadas, array $materiasPlanCursadaAprobadas)
    {
        $correlativas = PlanEstudioCorrelativa::with('materiaCorrelativa')
            ->where('ID_Materia', $idMateriaPlan)
            ->where('B', 0)
            ->get();

        if ($correlativas->isEmpty()) {
            return ['cumple' => true, 'causal' => ''];
        }

        $cantAprobadas = 0;
        $causales = [];

        foreach ($correlativas as $correlativa) {
            $idRequerido = $correlativa->ID_Materia_C;
            $tipo = $correlativa->Tipo; // 1 = Cursada, otro = Final
            $nombreCorrelativa = $correlativa->materiaCorrelativa->Materia ?? 'Materia Desconocida';
            
            $aprobada = false;

            if ($tipo == 1) {
                // Requiere CURSADA (o Final, si tiene final obvio tiene cursada)
                if (in_array($idRequerido, $materiasPlanCursadaAprobadas) || in_array($idRequerido, $materiasPlanFinalAprobadas)) {
                    $aprobada = true;
                } else {
                    // Texto idéntico a la imagen e8cfb1.jpg
                    $causales[] = "Cursada bloqueada por ausencia de Cursada de " . $nombreCorrelativa;
                }
            } else {
                // Requiere FINAL SI O SI
                if (in_array($idRequerido, $materiasPlanFinalAprobadas)) {
                    $aprobada = true;
                } else {
                    // Texto idéntico a la imagen e8cfb1.jpg
                    $causales[] = "Cursada bloqueada por ausencia de Final de " . $nombreCorrelativa;
                }
            }

            if ($aprobada) {
                $cantAprobadas++;
            }
        }

        if ($cantAprobadas >= $correlativas->count()) {
            return ['cumple' => true, 'causal' => ''];
        } else {
            return [
                'cumple' => false, 
                'causal' => implode(' | ', $causales)
            ];
        }
    }
}