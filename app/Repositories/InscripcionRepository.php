<?php

namespace App\Repositories;

use App\Models\Alumno;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;
use Log;

class InscripcionRepository
{
    /**
     * Define los límites de materias. 
     * Solo consulta la API externa si la institución es la 21.
     */
    private function definirLimitesMatricula(int $institucionId, int $alumnoId): array
    {
        if ($institucionId !== 21) {
            return ['min' => 0, 'max' => 999];
        }

        return $this->obtenerLimitesDeMateriasExterna($institucionId, $alumnoId);
    }

    /**
     * Consulta la API de facturación (Solo para ID_INSTITUCION = 21)
     */
    private function obtenerLimitesDeMateriasExterna(int $institucionId, int $alumnoId)
    {
        $limites = ['min' => 0, 'max' => 999];

        try {
            $url = "https://apirest.geofacturacion.com.ar/api/actividades/ver_alumno/{$institucionId}?id={$alumnoId}";

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
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

                    if (preg_match('/(\d+)\s*a\s*(\d+)\s*materias/i', $nombreActividad, $matches)) {
                        $limites = ['min' => (int) $matches[1], 'max' => (int) $matches[2]];
                    } elseif (preg_match('/(\d+)\s*materias?/i', $nombreActividad, $matches)) {
                        $limites = ['min' => (int) $matches[1], 'max' => (int) $matches[1]];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("Excepción API Facturacion (Alumno {$alumnoId}): " . $e->getMessage());
        }

        return $limites;
    }

    public function getMateriasDisponibles(int $institucionId, int $alumnoId)
    {
        $alumno = Alumno::with(['curso.plan'])->find($alumnoId);
        if (!$alumno) throw new Exception("No pudimos encontrar tus datos de alumno.");

        $cursoActual = $alumno->curso;
        $cicloLectivo = DB::table('ciclo_lectivo')->where('ID_Nivel', $alumno->ID_Nivel)->where('Vigente', 'SI')->first();
        $parametros = DB::table('nivel_parametros')->where('ID_Nivel', $alumno->ID_Nivel)->first();
        
        $notasAlumno = DB::table('notas_cursada')->where('ID_Alumno', $alumnoId)->get();
        $inscripcionesActuales = $cicloLectivo ? DB::table('grupos')
            ->where('ID_Alumno', $alumnoId)
            ->where('ID_Ciclo_Lectivo', $cicloLectivo->ID)
            ->pluck('ID_Materia_Grupal')->toArray() : [];

        // 1. Calculamos el turno (Igual al legacy)
        $turnoAlumno = $this->obtenerTurnoAlumno($alumno, $cicloLectivo);

        // 2. Límites de inscripción
        $limites = $this->definirLimitesMatricula($institucionId, $alumnoId);
        $yaAnotadoCount = count($inscripcionesActuales);
        $limiteAlcanzado = ($yaAnotadoCount >= $limites['max']);

        $resultado = [];

        // 3. Recorremos el Plan de Estudios
        $cursosPlan = DB::table('planes_estudio_cursos')
            ->where('ID_Plan', $cursoActual->ID_Plan)
            ->where('Orden', '<=', $cursoActual->Orden_Plan)
            ->orderBy('Orden')->get();

        foreach ($cursosPlan as $cursoP) {
            $materiasPlan = DB::table('materias_planes')->where('ID_Plan', $cursoActual->ID_Plan)->where('Curso', $cursoP->ID)->get();

            foreach ($materiasPlan as $mPlan) {
                // Buscamos las instancias (materias)
                $instancias = DB::table('materias')->where('ID_Materia_Plan', $mPlan->ID);
                if ($cursoActual->Orden_Plan == $cursoP->Orden) {
                    $instancias->where('ID_Curso', $cursoActual->ID);
                }
                $instancias = $instancias->get();

                foreach ($instancias as $instancia) {
                    // ¿Ya la aprobó o la está cursando?
                    $yaAprobada = $notasAlumno->where('ID_Materia', $instancia->ID)
                        ->filter(function($n){ return $n->Final == 'SI' || $n->Cursada == 1; })->isNotEmpty();
                    
                    if ($yaAprobada) continue; // Si ya la hizo, no tiene sentido mostrarla

                    $grupos = DB::table('materias_grupales')
                        ->where('ID_Ciclo_Lectivo', optional($cicloLectivo)->ID)
                        ->where('ID_Materia', $instancia->ID)
                        ->where('AI', 'SI')->get();

                    foreach ($grupos as $grupo) {
                        $motivos = []; // Aquí guardaremos las razones para "Dummies"
                        
                        // REGLA: ¿Está anotado ya?
                        $inscripto = in_array($grupo->ID, $inscripcionesActuales);

                        // REGLA: Correlativas (Corregido a tabla _cursada según legacy)
                        $evalCorr = $this->validarCorrelativasFriendly($mPlan->ID, $alumnoId);
                        if (!$evalCorr['autorizado']) {
                            $motivos[] = $evalCorr['mensaje'];
                        }

                        // REGLA: Turno
                        if (!empty($turnoAlumno) && $turnoAlumno !== $grupo->Turno) {
                            $t = ['M' => 'Mañana', 'T' => 'Tarde', 'N' => 'Noche'][$grupo->Turno] ?? 'otro turno';
                            $motivos[] = "Este grupo es del turno $t y vos pertenecés a otro turno.";
                        }

                        // REGLA: Cupo
                        $restantes = (int)$grupo->Cupo - (int)$grupo->Alumnos;
                        if ($grupo->Cupo > 0 && $restantes <= 0) {
                            $motivos[] = "¡Llegaste tarde! El grupo ya está lleno.";
                        }

                        // REGLA: Límite de materias
                        if ($limiteAlcanzado && !$inscripto) {
                            $motivos[] = "Ya te anotaste a " . $limites['max'] . " materias, no podés sumar más.";
                        }

                        // REGLA: Período cerrado
                        if (optional($parametros)->HabAI != 1) {
                            $motivos[] = "El período de inscripciones no está abierto ahora.";
                        }

                        // REGLA: Situación del alumno
                        if ($alumno->ID_Situacion != 2) {
                            $motivos[] = "Tu estado actual como alumno no te permite anotarte a materias.";
                        }

                        $puedeInscribirse = (count($motivos) === 0 && !$inscripto);

                        $resultado[] = [
                            'id_materia_grupal' => $grupo->ID,
                            'materia' => trim($grupo->Materia),
                            'docente' => DB::table('personal')->where('ID', $grupo->ID_Personal)->value('Apellido') ?? 'A designar',
                            'cupo_disponible' => ($grupo->Cupo == 0) ? "Hay lugar" : ($restantes > 0 ? "$restantes lugares" : "Lleno"),
                            'estado' => $inscripto ? 'INSCRIPTO' : ($puedeInscribirse ? 'DISPONIBLE' : 'BLOQUEADA'),
                            'permite_inscripcion' => $puedeInscribirse,
                            'motivos_bloqueo' => $inscripto ? "Ya estás anotado acá." : implode(' ', $motivos),
                            'permite_excepcion' => !$evalCorr['autorizado'] && optional($parametros)->HabSE == 1 && !$inscripto
                        ];
                    }
                }
            }
        }

        return [
            'status' => 'success',
            'insc_disponibles' => max(0, $limites['max'] - $yaAnotadoCount),
            'data' => [
                'plan' => optional($cursoActual->plan)->Nombre ?? 'Tu Plan',
                'disponibles' => array_values($resultado)
            ]
        ];
    }

    private function validarCorrelativasFriendly(int $materiaPlanId, int $alumnoId)
    {
        $correlativas = DB::table('planes_estudio_correlativas_cursada')
            ->where('ID_Materia', $materiaPlanId)->where('B', 0)->get();

        foreach ($correlativas as $c) {
            $instanciasCorr = DB::table('materias')->where('ID_Materia_Plan', $c->ID_Materia_C)->pluck('ID')->toArray();
            $tipoReq = ($c->Tipo == 1) ? "cursada" : "final aprobado";
            
            $cumple = DB::table('notas_cursada')->where('ID_Alumno', $alumnoId)
                ->whereIn('ID_Materia', $instanciasCorr)
                ->where(function($q) use ($c) {
                    if ($c->Tipo == 1) $q->where('Cursada', 1);
                    else $q->where('Final', 'SI');
                })->exists();

            if (!$cumple) {
                $nomMateria = DB::table('materias_planes')->where('ID', $c->ID_Materia_C)->value('Materia');
                return [
                    'autorizado' => false, 
                    'mensaje' => "Te falta tener la $tipoReq de $nomMateria."
                ];
            }
        }
        return ['autorizado' => true, 'mensaje' => ''];
    }

    private function obtenerTurnoAlumno($alumno, $ciclo)
    {
        if (!empty($alumno->Turno)) return $alumno->Turno;
        return DB::table('grupos as g')
            ->join('materias_grupales as mg', 'g.ID_Materia_Grupal', '=', 'mg.ID')
            ->where('g.ID_Alumno', $alumno->ID)
            ->where('g.ID_Ciclo_Lectivo', '<', 4)
            ->orderBy('g.ID', 'desc')->value('mg.Turno');
    }

    public function inscribir(int $institucionId, int $alumnoId, int $idMateriaGrupal)
    {
        // Validación de límites (OPTIMIZADA)
        $limitesMatricula = $this->definirLimitesMatricula($institucionId, $alumnoId);

        return DB::transaction(function () use ($institucionId, $alumnoId, $idMateriaGrupal, $limitesMatricula) {
            $alumno = DB::table('alumnos')->where('ID', $alumnoId)->first();
            if (!$alumno) throw new \Exception('Alumno no encontrado.');

            $parametros = DB::table('nivel_parametros')->where('ID_Nivel', $alumno->ID_Nivel)->first();
            if (!$parametros || $parametros->HabAI != 1) {
                throw new \Exception('El período de inscripciones no se encuentra abierto.');
            }

            $ciclo = DB::table('ciclo_lectivo')->where('ID_Nivel', $alumno->ID_Nivel)->where('Vigente', 'SI')->first();

            // Validar límite máximo
            $cantidadInscriptas = DB::table('grupos')
                ->where('ID_Alumno', $alumnoId)
                ->where('ID_Ciclo_Lectivo', optional($ciclo)->ID)
                ->count();

            if ($cantidadInscriptas >= $limitesMatricula['max']) {
                throw new \Exception("Has alcanzado el límite máximo de materias permitidas ({$limitesMatricula['max']}).");
            }

            // Evitar duplicados por materia de plan
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

            if ($yaInscriptoEnPlan) throw new \Exception('Ya te encontrás inscripto en esta materia.');

            // Control de Cupo con bloqueo de fila
            $grupo = DB::table('materias_grupales')->where('ID', $idMateriaGrupal)->lockForUpdate()->first();
            if (!$grupo) throw new \Exception('El grupo no existe.');

            if ($grupo->Cupo > 0 && $grupo->Alumnos >= $grupo->Cupo) {
                throw new \Exception('El grupo se encuentra completo.');
            }

            // Inscripción
            DB::table('grupos')->insert([
                'ID_Alumno' => $alumnoId,
                'ID_Materia_Grupal' => $idMateriaGrupal,
                'ID_Ciclo_Lectivo' => optional($ciclo)->ID,
                'Fecha_Inscripcion' => date('Y-m-d H:i:s')
            ]);

            DB::table('materias_grupales')->where('ID', $idMateriaGrupal)->increment('Alumnos');

            return true;
        });
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
                return ['autorizado' => false, 'causal' => "Bloqueado por ausencia de $tipo de $nombreM"];
            }
        }
        return ['autorizado' => true, 'causal' => ''];
    }
    private function validarCorrelativasCursada(int $idMateriaPlan, int $alumnoId)
    {
        $correlativas = DB::table('planes_estudio_correlativas_cursada')
            ->where('ID_Materia', $idMateriaPlan)
            ->where('B', 0)
            ->get();

        if ($correlativas->isEmpty()) {
            return ['autorizado' => true, 'causal' => ''];
        }

        foreach ($correlativas as $corr) {
            $instanciasCorr = DB::table('materias')->where('ID_Materia_Plan', $corr->ID_Materia_C)->pluck('ID')->toArray();

            if (empty($instanciasCorr)) {
                continue; // Evita error en whereIn vacíos en Laravel 5.5
            }

            $cumple = DB::table('notas_cursada')
                ->where('ID_Alumno', $alumnoId)
                ->whereIn('ID_Materia', $instanciasCorr)
                ->where(function ($q) use ($corr) {
                    if ($corr->Tipo == 1) {
                        $q->where('Cursada', 1);
                    } else {
                        $q->where('Final', 'SI');
                    }
                })->exists();

            if (!$cumple) {
                $nombreM = DB::table('materias_planes')->where('ID', $corr->ID_Materia_C)->value('Materia');
                $tipo = ($corr->Tipo == 1) ? "Cursada" : "Final";
                return ['autorizado' => false, 'causal' => "Cursada bloqueada por ausencia de $tipo de $nombreM"];
            }
        }

        return ['autorizado' => true, 'causal' => ''];
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
