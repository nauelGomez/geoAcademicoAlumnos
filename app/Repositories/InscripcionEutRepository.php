<?php

namespace App\Repositories;

use App\Models\Alumno;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;

class InscripcionEutRepository
{
public function getMateriasDisponibles(int $alumnoId)
{
    $alumno = Alumno::with(['curso.plan'])->find($alumnoId);
    if (!$alumno) throw new Exception("Alumno no encontrado.");

    $cursoActual = $alumno->curso;
    $esInstitucion21 = (optional($cursoActual)->ID_Institucion == 21);

    // Obtenemos el ciclo vigente
    $cicloLectivo = DB::table('ciclo_lectivo')
        ->where('ID_Nivel', $alumno->ID_Nivel)
        ->where('Vigente', 'SI')->first();

    $parametros = DB::table('nivel_parametros')->where('ID_Nivel', $alumno->ID_Nivel)->first();
    $habilitadoGeneral = (optional($parametros)->HabAI == 1); //

    $resultado = [];

    // 1. Traemos TODAS las materias del plan sin filtros
    $materiasPlan = DB::table('materias_planes')
        ->where('ID_Plan', $cursoActual->ID_Plan)
        ->orderBy('Orden')->get();

    foreach ($materiasPlan as $mPlan) {
        $estado = "BLOQUEADA";
        $motivo = "";
        $grupoEncontrado = null;

        // A. Verificar si existe la materia física
        $instancia = DB::table('materias')
            ->where('ID_Materia_Plan', $mPlan->ID)
            ->first();

        if (!$instancia) {
            $estado = "NO DISPONIBLE";
            $motivo = "No existe registro en tabla 'materias' para este ID_Materia_Plan";
        } else {
            // B. Verificar Notas
            $hasFinalCol = Schema::hasColumn('notas_cursada', 'Final');
            $hasCursadaCol = Schema::hasColumn('notas_cursada', 'Cursada');

            $yaTieneNota = DB::table('notas_cursada')
                ->where('ID_Alumno', $alumnoId)
                ->where('ID_Materia', $instancia->ID)
                ->where(function($q) use ($hasFinalCol, $hasCursadaCol) {
                    if ($hasFinalCol) $q->where('Final', 'SI');
                    if ($hasCursadaCol) $q->orWhere('Cursada', 1);
                })->exists();

            if ($yaTieneNota) {
                $estado = "FINALIZADA";
                $motivo = "El alumno ya aprobó o regularizó esta materia";
            } else {
                // C. Buscar la oferta real en materias_grupales
                $grupoEncontrado = DB::table('materias_grupales')
                    ->where('ID_Materia', $instancia->ID)
                    ->where('AI', 'SI') //
                    ->when($cicloLectivo, function($q) use ($cicloLectivo) {
                        return $q->where('ID_Ciclo_Lectivo', $cicloLectivo->ID);
                    })
                    ->first();

                if (!$grupoEncontrado) {
                    $estado = "NO OFERTADA";
                    $motivo = "No hay grupo con AI='SI' para el ciclo vigente";
                } else {
                    // D. Verificar Inscripción previa
                    $yaInscripto = DB::table('grupos')
                        ->where('ID_Alumno', $alumnoId)
                        ->where('ID_Materia_Grupal', $grupoEncontrado->ID)
                        ->exists();

                    if ($yaInscripto) {
                        $estado = "INSCRIPTO";
                        $motivo = "El alumno ya posee una reserva activa";
                    } else {
                        // E. Verificar Cupo
                        $restantes = (int)$grupoEncontrado->Cupo - (int)$grupoEncontrado->Alumnos;
                        if ($grupoEncontrado->Cupo > 0 && $restantes <= 0) {
                            $estado = "CUPO LLENO";
                            $motivo = "El grupo alcanzó su capacidad máxima";
                        } else {
                            // F. Verificar Correlativas y Parámetros
                            $eval = $this->validarCorrelativasLegacy($mPlan->ID, $alumnoId);
                            if (!$eval['autorizado']) {
                                $estado = "BLOQUEADA";
                                $motivo = $eval['causal'];
                            } elseif (!$habilitadoGeneral) {
                                $estado = "PERIODO CERRADO";
                                $motivo = "Inscripción deshabilitada en nivel_parametros (HabAI=0)";
                            } else {
                                $estado = "HABILITADA";
                                $motivo = "Cumple con todos los requisitos";
                            }
                        }
                    }
                }
            }
        }

        $resultado[] = [
            'materia' => $mPlan->Materia,
            'id_materia_plan' => $mPlan->ID,
            'estado' => $estado,
            'motivo' => $motivo,
            'cupo_actual' => $grupoEncontrado ? ($grupoEncontrado->Cupo - $grupoEncontrado->Alumnos) : 0,
            'ai_status' => $grupoEncontrado ? $grupoEncontrado->AI : 'N/A'
        ];
    }

    return [
        'status' => 'success',
        'data' => [
            'plan' => optional($alumno->curso->plan)->Nombre ?? 'Diplomatura',
            'analisis' => $resultado
        ]
    ];
}

    private function validarCorrelativasLegacy(int $idMateriaPlan, int $alumnoId)
    {
        $correlativas = DB::table('planes_estudio_correlativas')->where('ID_Materia', $idMateriaPlan)->where('B', 0)->get();
        if ($correlativas->isEmpty()) return ['autorizado' => true, 'causal' => ''];

        $hasFinalCol = Schema::hasColumn('notas_cursada', 'Final');
        $hasCursadaCol = Schema::hasColumn('notas_cursada', 'Cursada');

        foreach ($correlativas as $corr) {
            $instanciasCorr = DB::table('materias')->where('ID_Materia_Plan', $corr->ID_Materia_C)->pluck('ID');
            $cumple = DB::table('notas_cursada')
                ->where('ID_Alumno', $alumnoId)
                ->whereIn('ID_Materia', $instanciasCorr)
                ->where(function($q) use ($corr, $hasFinalCol, $hasCursadaCol) {
                    if ($corr->Tipo == 1 && $hasCursadaCol) $q->where('Cursada', 1);
                    elseif ($corr->Tipo != 1 && $hasFinalCol) $q->where('Final', 'SI');
                    else $q->whereRaw('1=0');
                })->exists();

            if (!$cumple) {
                $nombreM = DB::table('materias_planes')->where('ID', $corr->ID_Materia_C)->value('Materia');
                return ['autorizado' => false, 'causal' => "Cursada bloqueada por ausencia de " . ($corr->Tipo == 1 ? "Cursada" : "Final") . " de $nombreM"];
            }
        }
        return ['autorizado' => true, 'causal' => ''];
    }

    /**
     * Inscribe al alumno y actualiza el contador de alumnos atómicamente
     */
    public function inscribir(int $alumnoId, int $idMateriaGrupal)
    {
        return DB::transaction(function () use ($alumnoId, $idMateriaGrupal) {
            $alumno = DB::table('alumnos')->where('ID', $alumnoId)->first();
            $parametros = DB::table('nivel_parametros')->where('ID_Nivel', $alumno->ID_Nivel)->first();
            
            if (!$parametros || $parametros->HabAI != 1) throw new \Exception('Período cerrado.');

            $ciclo = DB::table('ciclo_lectivo')->where('ID_Nivel', $alumno->ID_Nivel)->where('Vigente', 'SI')->first();

            // Bloqueo de fila para evitar Race Conditions en el cupo
            $grupo = DB::table('materias_grupales')->where('ID', $idMateriaGrupal)->lockForUpdate()->first();
            
            if ($grupo->Cupo > 0 && $grupo->Alumnos >= $grupo->Cupo) {
                throw new \Exception('Grupo completo.');
            }

            DB::table('grupos')->insert([
                'ID_Alumno' => $alumnoId,
                'ID_Materia_Grupal' => $idMateriaGrupal,
                'ID_Ciclo_Lectivo' => optional($ciclo)->ID,
                'Fecha_Inscripcion' => date('Y-m-d H:i:s')
            ]);

            // Incremento atómico del contador desnormalizado
            DB::table('materias_grupales')->where('ID', $idMateriaGrupal)->increment('Alumnos');

            return true;
        });
    }

    /**
     * Da de baja la inscripción y libera el cupo
     */
    public function darDeBaja(int $alumnoId, int $idMateriaGrupal)
    {
        return DB::transaction(function () use ($alumnoId, $idMateriaGrupal) {
            $eliminado = DB::table('grupos')
                ->where('ID_Alumno', $alumnoId)
                ->where('ID_Materia_Grupal', $idMateriaGrupal)
                ->delete();

            if ($eliminado) {
                // Decremento atómico del contador
                DB::table('materias_grupales')->where('ID', $idMateriaGrupal)->decrement('Alumnos');
                return true;
            }
            throw new \Exception('No se encontró la inscripción.');
        });
    }
}