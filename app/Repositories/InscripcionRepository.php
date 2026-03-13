<?php

namespace App\Repositories;

use App\Models\Alumno;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;

class InscripcionRepository
{
  public function getMateriasDisponibles(int $alumnoId)
    {
        $alumno = Alumno::with(['curso.plan'])->find($alumnoId);
        if (!$alumno) throw new Exception("Alumno no encontrado.");

        $cursoActual = $alumno->curso;
        $cicloLectivo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')->first();

        $parametros = DB::table('nivel_parametros')->where('ID_Nivel', $alumno->ID_Nivel)->first();
        
        $resultado = [];

        // --- OPTIMIZACIÓN DE PERFORMANCE (Evitar N+1) ---
        // 1. Traemos TODAS las notas del alumno de una vez
        $notasAlumno = DB::table('notas_cursada')->where('ID_Alumno', $alumnoId)->get();

        // 2. Traemos TODAS las inscripciones actuales del alumno en este ciclo
        // Retorna un array clave-valor: [ID_Materia_Plan => ID_Materia_Grupal]
        $inscripcionesActuales = [];
        if ($cicloLectivo) {
            $inscripcionesActuales = DB::table('grupos')
                ->join('materias_grupales', 'grupos.ID_Materia_Grupal', '=', 'materias_grupales.ID')
                ->join('materias', 'materias_grupales.ID_Materia', '=', 'materias.ID')
                ->where('grupos.ID_Alumno', $alumnoId)
                ->where('grupos.ID_Ciclo_Lectivo', $cicloLectivo->ID)
                ->pluck('grupos.ID_Materia_Grupal', 'materias.ID_Materia_Plan')
                ->toArray();
        }
        // ------------------------------------------------

        // Obtenemos cursos del plan hasta el año actual del alumno (Filtro por Orden)
        $cursosPlan = DB::table('planes_estudio_cursos')
            ->where('ID_Plan', $cursoActual->ID_Plan)
            ->where('Orden', '<=', $cursoActual->Orden_Plan)
            ->orderBy('Orden')->get();

        foreach ($cursosPlan as $cursoP) {
            // Materias definidas en el plan para ese año
            $materiasPlan = DB::table('materias_planes')
                ->where('ID_Plan', $cursoActual->ID_Plan)
                ->where('Curso', $cursoP->ID)
                ->orderBy('Orden')->get();

            foreach ($materiasPlan as $mPlan) {
                // Instancias físicas (materias). Fix para DBs sin columna 'Turno' (Institución 21)
                $queryMat = DB::table('materias')->where('ID_Materia_Plan', $mPlan->ID);

                if (Schema::hasColumn('materias', 'Turno') && !empty($cursoActual->Turno)) {
                    $queryMat->where('Turno', $cursoActual->Turno);
                }

                if ($cursoP->Orden == $cursoActual->Orden_Plan) {
                    $queryMat->where('ID_Curso', $alumno->ID_Curso);
                }

                $instancias = $queryMat->get();

                foreach ($instancias as $instancia) {
                    // CHEQUEO NOTAS: Usamos la colección en memoria (0 queries extra)
                    $tieneNota = $notasAlumno->where('ID_Materia', $instancia->ID)
                        ->filter(function($nota) {
                            return $nota->Final == 'SI' || $nota->Cursada == 1;
                        })->isNotEmpty();

                    if ($tieneNota) continue;

                    // Oferta de grupos activos (AI = 'SI')
                    $grupos = DB::table('materias_grupales')
                        ->where('ID_Ciclo_Lectivo', optional($cicloLectivo)->ID)
                        ->where('ID_Materia', $instancia->ID)
                        ->where('AI', 'SI')->get();

                    foreach ($grupos as $grupo) {
                        
                        // --- VALIDACIÓN DE INSCRIPCIÓN ---
                        $grupoInscriptoEnPlan = $inscripcionesActuales[$mPlan->ID] ?? null;
                        $yaInscriptoEnEsteGrupo = ($grupoInscriptoEnPlan == $grupo->ID);
                        $yaInscriptoEnOtroGrupo = ($grupoInscriptoEnPlan && $grupoInscriptoEnPlan != $grupo->ID);

                        // --- CÁLCULO DE CUPO REAL USANDO COLUMNA 'Alumnos' ---
                        $cupoMax = (int)$grupo->Cupo; 
                        $inscriptos = (int)$grupo->Alumnos; 
                        $restantes = $cupoMax - $inscriptos; 
                        
                        $disponibilidadTexto = "";
                        $hayLugar = false;

                        if ($cupoMax == 0) {
                            $disponibilidadTexto = "Lugares Disponibles";
                            $hayLugar = true;
                        } else {
                            if ($restantes > 0) {
                                $disponibilidadTexto = $restantes . ($restantes == 1 ? " lugar" : " lugares");
                                $hayLugar = true;
                            } else {
                                $disponibilidadTexto = "Grupo Completo";
                                $hayLugar = false;
                            }
                        }

                        $eval = $this->validarCorrelativasLegacy($mPlan->ID, $alumnoId);
                        
                        // --- RESOLUCIÓN DE ESTADOS ---
                        $estado = 'DISPONIBLE';
                        $puedeInscribirse = false;
                        $motivosBloqueo = '';

                        if ($yaInscriptoEnEsteGrupo) {
                            $estado = 'INSCRIPTO';
                            $motivosBloqueo = 'Ya te encontrás inscripto en este grupo.';
                        } elseif ($yaInscriptoEnOtroGrupo) {
                            $estado = 'BLOQUEADA';
                            $motivosBloqueo = 'Ya te encontrás inscripto en otro grupo de esta materia.';
                        } else {
                            $puedeInscribirse = ($eval['autorizado'] && $hayLugar && optional($parametros)->HabAI == 1 && $alumno->ID_Situacion == 2);
                            $estado = $puedeInscribirse ? 'DISPONIBLE' : 'BLOQUEADA';
                            $motivosBloqueo = $eval['autorizado'] ? "" : $eval['causal'];
                        }

                        // Evitamos disparar una query por cada iteración si no hay profesor asignado
                        $nombreDocente = DB::table('personal')->where('ID', $grupo->ID_Personal)->value('Apellido');
                        $docenteStr = $nombreDocente ? 'Prof. ' . $nombreDocente : 'A Designar';

                        $resultado[] = [
                            'id_materia_grupal' => $grupo->ID,
                            'materia' => $grupo->Materia,
                            'docente' => $docenteStr,
                            'cupo_disponible' => $disponibilidadTexto,
                            'permite_inscripcion' => $puedeInscribirse,
                            'estado' => $estado,
                            'motivos_bloqueo' => $motivosBloqueo,
                            'permite_excepcion' => !$eval['autorizado'] && (optional($parametros)->HabSE == 1) && !$yaInscriptoEnEsteGrupo && !$yaInscriptoEnOtroGrupo
                        ];
                    }
                }
            }
        }

        return [
            'status' => 'success',
            'data' => [
                'plan' => optional($alumno->curso->plan)->Nombre ?? 'Sin Plan',
                'disponibles' => array_values($resultado)
            ]
        ];
    }

    /**
     * Valida correlativas buscando aprobación en cualquier instancia de la materia del plan
     */
    private function validarCorrelativasLegacy(int $idMateriaPlan, int $alumnoId)
    {
        $correlativas = DB::table('planes_estudio_correlativas')
            ->where('ID_Materia', $idMateriaPlan)
            ->where('B', 0)->get();

        if ($correlativas->isEmpty()) return ['autorizado' => true, 'causal' => ''];

        foreach ($correlativas as $corr) {
            $instanciasCorr = DB::table('materias')->where('ID_Materia_Plan', $corr->ID_Materia_C)->pluck('ID');
            $cumple = DB::table('notas_cursada')
                ->where('ID_Alumno', $alumnoId)
                ->whereIn('ID_Materia', $instanciasCorr)
                ->where(function($q) use ($corr) {
                    if ($corr->Tipo == 1) $q->where('Cursada', 1);
                    else $q->where('Final', 'SI');
                })->exists();

            if (!$cumple) {
                $nombreM = DB::table('materias_planes')->where('ID', $corr->ID_Materia_C)->value('Materia');
                $tipo = ($corr->Tipo == 1) ? "Cursada" : "Final";
                return ['autorizado' => false, 'causal' => "Cursada bloqueada por ausencia de $tipo de $nombreM"];
            }
        }
        return ['autorizado' => true, 'causal' => ''];
    }

    /**
     * Procesa la inscripción del alumno
     */
    public function inscribir(int $alumnoId, int $idMateriaGrupal)
    {
        return DB::transaction(function () use ($alumnoId, $idMateriaGrupal) {
            $alumno = DB::table('alumnos')->where('ID', $alumnoId)->first();
            if (!$alumno) throw new \Exception('Alumno no encontrado.');

            $parametros = DB::table('nivel_parametros')->where('ID_Nivel', $alumno->ID_Nivel)->first();
            if (!$parametros || $parametros->HabAI != 1) {
                throw new \Exception('El período de inscripciones no se encuentra abierto.');
            }

            $ciclo = DB::table('ciclo_lectivo')->where('ID_Nivel', $alumno->ID_Nivel)->where('Vigente', 'SI')->first();

            // 1. Evitar doble inscripción en la misma materia del plan
            $materiaPlanDestino = DB::table('materias_grupales')
                ->join('materias', 'materias_grupales.ID_Materia', '=', 'materias.ID')
                ->where('materias_grupales.ID', $idMateriaGrupal)
                ->value('materias.ID_Materia_Plan');

            $yaInscriptoEnPlan = DB::table('grupos')
                ->join('materias_grupales', 'grupos.ID_Materia_Grupal', '=', 'materias_grupales.ID')
                ->join('materias', 'materias_grupales.ID_Materia', '=', 'materias.ID')
                ->where('grupos.ID_Alumno', $alumnoId)
                ->where('grupos.ID_Ciclo_Lectivo', optional($ciclo)->ID)
                ->where('materias.ID_Materia_Plan', $materiaPlanDestino)
                ->exists();

            if ($yaInscriptoEnPlan) throw new \Exception('Ya te encontrás inscripto en una cursada de esta materia.');

            // 2. Bloqueo de fila para evitar race conditions en el cupo
            $grupo = DB::table('materias_grupales')->where('ID', $idMateriaGrupal)->lockForUpdate()->first();
            if (!$grupo) throw new \Exception('El grupo no existe.');

            if ($grupo->Cupo > 0) {
                if ($grupo->Alumnos >= $grupo->Cupo) {
                    throw new \Exception('El grupo se encuentra completo. No hay cupos disponibles.');
                }
            }

            // 3. Insertar registro
            DB::table('grupos')->insert([
                'ID_Alumno' => $alumnoId,
                'ID_Materia_Grupal' => $idMateriaGrupal,
                'ID_Ciclo_Lectivo' => optional($ciclo)->ID,
                'Fecha_Inscripcion' => date('Y-m-d H:i:s')
            ]);

            // 4. Actualizar contador desnormalizado
            DB::table('materias_grupales')->where('ID', $idMateriaGrupal)->increment('Alumnos');

            return true;
        });
    }

    /**
     * Cancela la inscripción y libera el cupo
     */
    public function darDeBaja(int $alumnoId, int $idMateriaGrupal)
    {
        return DB::transaction(function () use ($alumnoId, $idMateriaGrupal) {
            $alumno = DB::table('alumnos')->where('ID', $alumnoId)->first();
            $parametros = DB::table('nivel_parametros')->where('ID_Nivel', $alumno->ID_Nivel)->first();

            if (!$parametros || $parametros->HabAI != 1) {
                throw new \Exception('El período de inscripciones se encuentra cerrado. No podés cancelar la reserva.');
            }

            // 1. Verificar existencia y borrar
            $eliminado = DB::table('grupos')
                ->where('ID_Alumno', $alumnoId)
                ->where('ID_Materia_Grupal', $idMateriaGrupal)
                ->delete();

            if (!$eliminado) {
                throw new \Exception('No se encontró una inscripción vigente para cancelar.');
            }

            // 2. Decrementar contador atómicamente
            DB::table('materias_grupales')->where('ID', $idMateriaGrupal)->decrement('Alumnos');

            return true;
        });
    }
}