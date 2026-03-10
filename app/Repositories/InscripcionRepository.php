<?php
namespace App\Repositories;

use App\Models\Alumno;
use App\Models\MateriaGrupal;
use App\Models\CicloLectivo;
use App\Models\NivelParametro;
use App\Models\PlanEstudioCorrelativa;
use Illuminate\Support\Facades\DB;
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

        $materiasAprobadasIds = $alumno->notasCursada->where('Final', 'SI')->pluck('ID_Materia')->toArray();
        $materiasCursadasIds = $alumno->notasCursada->where('Cursada', 1)->pluck('ID_Materia')->toArray();

        $materiasOcultasIds = array_unique(array_merge($materiasAprobadasIds, $materiasCursadasIds));

        $materiasPlanAprobadasFinal = DB::table('materias')
            ->whereIn('ID', $materiasAprobadasIds)
            ->pluck('ID_Materia_Plan')->toArray();

        $materiasPlanAprobadasCursada = DB::table('materias')
            ->whereIn('ID', $materiasCursadasIds)
            ->pluck('ID_Materia_Plan')->toArray();

        $gruposDisponibles = MateriaGrupal::withCount('inscripciones')
            ->join('materias', 'materias.ID', '=', 'materias_grupales.ID_Materia')
            ->join('materias_planes', 'materias_planes.ID', '=', 'materias.ID_Materia_Plan')
            ->join('personal', 'personal.ID', '=', 'materias_grupales.ID_Personal')
            ->where('materias_grupales.ID_Ciclo_Lectivo', optional($cicloLectivo)->ID)
            ->where('materias_grupales.AI', 'SI')
            ->where('materias.Turno', optional($alumno->curso)->Turno)
            ->whereNotIn('materias_grupales.ID_Materia', $materiasOcultasIds)
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

            $evaluacionCorrelativas = $this->checkCorrelativas(
                $grupo->ID_Materia_Plan, 
                $materiasPlanAprobadasFinal, 
                $materiasPlanAprobadasCursada
            );
            
            if (!$evaluacionCorrelativas['cumple']) {
                $causales[] = $evaluacionCorrelativas['causal'];
                $puedeInscribirse = false;
            }

            $solicitudPendiente = DB::table('solicitudes_excepcion_cursada')
                ->where('ID_Materia', $grupo->Materia_Instancia_ID)
                ->where('ID_Alumno', $alumno->ID)
                ->where('ID_Ciclo_Lectivo', optional($cicloLectivo)->ID)
                ->where('B', 0)
                ->exists();

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
            $tipo = $correlativa->Tipo;
            $nombreCorrelativa = $correlativa->materiaCorrelativa->Materia ?? 'Materia Desconocida';
            
            $aprobada = false;

            if ($tipo == 1) {
                if (in_array($idRequerido, $materiasPlanCursadaAprobadas) || in_array($idRequerido, $materiasPlanFinalAprobadas)) {
                    $aprobada = true;
                } else {
                    $causales[] = "Cursada bloqueada por ausencia de Cursada de " . $nombreCorrelativa;
                }
            } else {
                if (in_array($idRequerido, $materiasPlanFinalAprobadas)) {
                    $aprobada = true;
                } else {
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

    // <-- FIX: Se reincorporaron los métodos de gestionar la inscripción
    public function inscribir(int $alumnoId, int $idMateriaGrupal)
    {
        return DB::transaction(function () use ($alumnoId, $idMateriaGrupal) {
            $alumno = DB::table('alumnos')->where('ID', $alumnoId)->first();
            if (!$alumno) throw new \Exception('Alumno no encontrado.');

            $parametros = DB::table('nivel_parametros')->where('ID_Nivel', $alumno->ID_Nivel)->first();
            if (!$parametros || $parametros->HabAI != 1) {
                throw new \Exception('El período de inscripciones no se encuentra abierto.');
            }

            $ciclo = DB::table('ciclo_lectivo')
                ->where('ID_Nivel', $alumno->ID_Nivel)
                ->where('Vigente', 'SI')->first();

            $yaInscripto = DB::table('grupos')
                ->where('ID_Alumno', $alumnoId)
                ->where('ID_Materia_Grupal', $idMateriaGrupal)
                ->exists();

            if ($yaInscripto) {
                throw new \Exception('Ya te encuentras inscripto en este grupo.');
            }

            $grupo = DB::table('materias_grupales')
                ->where('ID', $idMateriaGrupal)
                ->lockForUpdate()
                ->first();

            if (!$grupo) throw new \Exception('El grupo no existe.');

            if ($grupo->Cupo > 0) {
                $inscriptos = DB::table('grupos')->where('ID_Materia_Grupal', $idMateriaGrupal)->count();
                if ($inscriptos >= $grupo->Cupo) {
                    throw new \Exception('El grupo se encuentra completo. No hay cupos disponibles.');
                }
            }

            DB::table('grupos')->insert([
                'ID_Alumno' => $alumnoId,
                'ID_Materia_Grupal' => $idMateriaGrupal,
                'ID_Ciclo_Lectivo' => $ciclo->ID,
                'Fecha_Inscripcion' => date('Y-m-d H:i:s')
            ]);

            return true;
        });
    }

    public function darDeBaja(int $alumnoId, int $idMateriaGrupal)
    {
        return DB::transaction(function () use ($alumnoId, $idMateriaGrupal) {
            $alumno = DB::table('alumnos')->where('ID', $alumnoId)->first();
            $parametros = DB::table('nivel_parametros')->where('ID_Nivel', $alumno->ID_Nivel)->first();
            
            if (!$parametros || $parametros->HabAI != 1) {
                throw new \Exception('El período de inscripciones se encuentra cerrado. No puedes cancelar la reserva.');
            }

            $eliminado = DB::table('grupos')
                ->where('ID_Alumno', $alumnoId)
                ->where('ID_Materia_Grupal', $idMateriaGrupal)
                ->delete();

            if (!$eliminado) {
                throw new \Exception('No se encontró una inscripción vigente para cancelar.');
            }

            return true;
        });
    }
}