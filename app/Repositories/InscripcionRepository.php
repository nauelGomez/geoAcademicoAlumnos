<?php

namespace App\Repositories;

use App\Models\Alumno;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;

class InscripcionRepository
{
    /**
     * Consulta la API de facturación para saber el límite de materias del alumno usando cURL nativo (Laravel 5.5 compatible)
     */
    private function obtenerLimitesDeMateriasExterna(int $institucionId, int $alumnoId)
    {
        // Fallback por defecto (infinitas) si la API falla o no tiene paquete asignado
        $limites = ['min' => 0, 'max' => 999];

        try {
            $url = "https://apirest.geofacturacion.com.ar/api/actividades/ver_alumno/{$institucionId}?id={$alumnoId}";

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPGET, true);

            // ---> ESTA ES LA LÍNEA MÁGICA QUE APAGA LA SEGURIDAD SSL EN LOCAL <---
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            curl_setopt($curl, CURLOPT_TIMEOUT, 3);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if ($httpCode >= 200 && $httpCode < 300 && !$error) {
                $data = json_decode($response, true);

                if (!empty($data['data']) && isset($data['data'][0]['nombre'])) {
                    $nombreActividad = trim($data['data'][0]['nombre']);

                    // Caso "2 a 7 materias"
                    if (preg_match('/(\d+)\s*a\s*(\d+)\s*materias/i', $nombreActividad, $matches)) {
                        $limites = ['min' => (int) $matches[1], 'max' => (int) $matches[2]];
                    }
                    // Caso "5 materias" o "1 materia"
                    elseif (preg_match('/(\d+)\s*materias?/i', $nombreActividad, $matches)) {
                        $limites = ['min' => (int) $matches[1], 'max' => (int) $matches[1]];
                    }
                }
            } else {
                \Log::warning("La API de facturación devolvió HTTP {$httpCode} o error: {$error}");
            }
        } catch (\Exception $e) {
            \Log::warning("Excepción al consultar API Facturacion para Alumno {$alumnoId}: " . $e->getMessage());
        }

        return $limites;
    }

    public function getMateriasDisponibles(int $institucionId, int $alumnoId)
    {
        $alumno = Alumno::with(['curso.plan'])->find($alumnoId);
        if (!$alumno) throw new Exception("Alumno no encontrado.");

        $cursoActual = $alumno->curso;
        $cicloLectivo = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel)
            ->where('Vigente', 'SI')->first();

        $parametros = DB::table('nivel_parametros')->where('ID_Nivel', $alumno->ID_Nivel)->first();

        $resultado = [];
        $notasAlumno = DB::table('notas_cursada')->where('ID_Alumno', $alumnoId)->get();

        // 1. Obtenemos solo los IDs de los grupos donde el alumno YA está inscripto en este ciclo
        $gruposDelAlumno = [];
        $materiasDelAlumno = [];
        if ($cicloLectivo) {
            $inscripciones = DB::table('grupos')
                ->join('materias_grupales', 'grupos.ID_Materia_Grupal', '=', 'materias_grupales.ID')
                ->where('grupos.ID_Alumno', $alumnoId)
                ->where('grupos.ID_Ciclo_Lectivo', $cicloLectivo->ID)
                ->select('grupos.ID_Materia_Grupal', 'materias_grupales.ID_Materia')
                ->get();

            $gruposDelAlumno = $inscripciones->pluck('ID_Materia_Grupal')->toArray();
            $materiasDelAlumno = $inscripciones->pluck('ID_Materia')->toArray();
        }

        // 2. Validación de tope de matrícula (API Facturación)
        $limitesMatricula = $this->obtenerLimitesDeMateriasExterna($institucionId, $alumnoId);
        $cantidadInscriptas = count($gruposDelAlumno);
        $inscDisponibles = max(0, $limitesMatricula['max'] - $cantidadInscriptas);
        $limiteAlcanzado = ($cantidadInscriptas >= $limitesMatricula['max']);

        // 3. Listado de materias por plan
        $cursosPlan = DB::table('planes_estudio_cursos')
            ->where('ID_Plan', $cursoActual->ID_Plan)
            ->where('Orden', '<=', $cursoActual->Orden_Plan)
            ->orderBy('Orden')->get();

        foreach ($cursosPlan as $cursoP) {
            $materiasPlan = DB::table('materias_planes')
                ->where('ID_Plan', $cursoActual->ID_Plan)
                ->where('Curso', $cursoP->ID)
                ->orderBy('Orden')->get();

            foreach ($materiasPlan as $mPlan) {
                // Instancias físicas de la materia
                $queryMat = DB::table('materias')->where('ID_Materia_Plan', $mPlan->ID);
                if (Schema::hasColumn('materias', 'Turno') && !empty($cursoActual->Turno)) {
                    $queryMat->where('Turno', $cursoActual->Turno);
                }
                $instancias = $queryMat->get();

                foreach ($instancias as $instancia) {
                    // Si ya la aprobó, no se muestra
                    if ($notasAlumno->where('ID_Materia', $instancia->ID)->filter(function ($n) {
                        return $n->Final == 'SI' || $n->Cursada == 1;
                    })->isNotEmpty()) continue;

                    // Grupos disponibles
                    $grupos = DB::table('materias_grupales')
                        ->where('ID_Ciclo_Lectivo', optional($cicloLectivo)->ID)
                        ->where('ID_Materia', $instancia->ID)
                        ->where('AI', 'SI')->get();

                    foreach ($grupos as $grupo) {

                        $yaInscriptoEnEsteGrupo = in_array($grupo->ID, $gruposDelAlumno);

                        // Eliminamos la validación de ID_Materia para que pueda 
                        // anotarse a cuantas materias quiera del mismo nivel/grupo.
                        $yaInscriptoEnOtroGrupoDeEstaMateria = false;
                        $cupoMax = (int)$grupo->Cupo;
                        $inscriptos = (int)$grupo->Alumnos;
                        $restantes = $cupoMax - $inscriptos;
                        $hayLugar = ($cupoMax == 0 || $restantes > 0);

                        $eval = $this->validarCorrelativasLegacy($mPlan->ID, $alumnoId);

                        $estado = 'DISPONIBLE';
                        $puedeInscribirse = false;
                        $permiteBaja = false;
                        $motivosBloqueo = '';

                        if ($yaInscriptoEnEsteGrupo) {
                            $estado = 'INSCRIPTO';
                            $permiteBaja = (optional($parametros)->HabAI == 1);
                            $motivosBloqueo = 'Ya te encontrás inscripto en este grupo.';
                        } elseif ($yaInscriptoEnOtroGrupoDeEstaMateria) {
                            $estado = 'BLOQUEADA';
                            $motivosBloqueo = 'Ya te encontrás inscripto en otro grupo de esta materia.';
                        } else {
                            if ($limiteAlcanzado) {
                                $estado = 'BLOQUEADA';
                                $motivosBloqueo = "Límite de matrícula alcanzado ({$limitesMatricula['max']}).";
                            } else {
                                $puedeInscribirse = ($eval['autorizado'] && $hayLugar && optional($parametros)->HabAI == 1 && $alumno->ID_Situacion == 2);
                                $estado = $puedeInscribirse ? 'DISPONIBLE' : 'BLOQUEADA';
                                $motivosBloqueo = $eval['autorizado'] ? ($hayLugar ? "" : "Cupo completo") : $eval['causal'];
                            }
                        }

                        $resultado[] = [
                            'id_materia_grupal' => $grupo->ID,
                            'materia' => trim($grupo->Materia),
                            'docente' => DB::table('personal')->where('ID', $grupo->ID_Personal)->value('Apellido') ?: 'A Designar',
                            'cupo_disponible' => ($cupoMax == 0) ? "Lugares Disponibles" : ($restantes > 0 ? "$restantes lugares" : "Grupo Completo"),
                            'estado' => $estado,
                            'permite_inscripcion' => $puedeInscribirse,
                            'permite_baja' => $permiteBaja,
                            'motivos_bloqueo' => $motivosBloqueo,
                            'permite_excepcion' => !$eval['autorizado'] && (optional($parametros)->HabSE == 1) && !$yaInscriptoEnEsteGrupo
                        ];
                    }
                }
            }
        }

        return [
            'status' => 'success',
            'insc_disponibles' => $inscDisponibles,
            'data' => [
                'plan' => optional($alumno->curso->plan)->Nombre ?? 'Sin Plan',
                'disponibles' => array_values($resultado),
            ]
        ];
    }

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
                ->where(function ($q) use ($corr) {
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

    public function inscribir(int $institucionId, int $alumnoId, int $idMateriaGrupal)
    {
        // 1. Validar límite externo ANTES de abrir la transacción (evita locks innecesarios)
        $limitesMatricula = $this->obtenerLimitesDeMateriasExterna($institucionId, $alumnoId);

        return DB::transaction(function () use ($institucionId, $alumnoId, $idMateriaGrupal, $limitesMatricula) {
            $alumno = DB::table('alumnos')->where('ID', $alumnoId)->first();
            if (!$alumno) throw new \Exception('Alumno no encontrado.');

            $parametros = DB::table('nivel_parametros')->where('ID_Nivel', $alumno->ID_Nivel)->first();
            if (!$parametros || $parametros->HabAI != 1) {
                throw new \Exception('El período de inscripciones no se encuentra abierto.');
            }

            $ciclo = DB::table('ciclo_lectivo')->where('ID_Nivel', $alumno->ID_Nivel)->where('Vigente', 'SI')->first();

            // 2. Bloqueo duro: Validar que no haya superado el límite (protección contra peticiones POST directas)
            $cantidadInscriptas = DB::table('grupos')
                ->where('ID_Alumno', $alumnoId)
                ->where('ID_Ciclo_Lectivo', optional($ciclo)->ID)
                ->count();

            if ($cantidadInscriptas >= $limitesMatricula['max']) {
                throw new \Exception("Has alcanzado el límite máximo de materias permitidas por tu matrícula ({$limitesMatricula['max']}).");
            }

            // 3. Evitar doble inscripción en la misma materia del plan
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

            // 4. Bloqueo de fila para evitar race conditions en el cupo (Overbooking)
            $grupo = DB::table('materias_grupales')->where('ID', $idMateriaGrupal)->lockForUpdate()->first();
            if (!$grupo) throw new \Exception('El grupo no existe.');

            if ($grupo->Cupo > 0) {
                if ($grupo->Alumnos >= $grupo->Cupo) {
                    throw new \Exception('El grupo se encuentra completo. No hay cupos disponibles.');
                }
            }

            // 5. Insertar registro
            DB::table('grupos')->insert([
                'ID_Alumno' => $alumnoId,
                'ID_Materia_Grupal' => $idMateriaGrupal,
                'ID_Ciclo_Lectivo' => optional($ciclo)->ID,
                'Fecha_Inscripcion' => date('Y-m-d H:i:s')
            ]);

            // 6. Actualizar contador desnormalizado
            DB::table('materias_grupales')->where('ID', $idMateriaGrupal)->increment('Alumnos');

            return true;
        });
    }

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
