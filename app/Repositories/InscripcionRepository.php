<?php

namespace App\Repositories;

use App\Models\Alumno;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema; // <-- 1. IMPORTANTE: Agregamos el Facade Schema
use Exception;

class InscripcionRepository
{
    /**
     * Consulta los límites de la API externa para todas las instituciones.
     */
    private function obtenerLimitesExternos(int $institucionId, int $alumnoId): array
    {
        $res = [
            'fallida' => false,
            'limites' => []
        ];

        try {
            $url = "https://apirest.geofacturacion.com.ar/api/actividades/ver_alumno/{$institucionId}?id={$alumnoId}";
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300 && $response) {
                $json = json_decode($response, true);
                
                if (!empty($json['data']) && is_array($json['data'])) {
                    foreach ($json['data'] as $data) {
                        if (isset($data['id'])) {
                            // Extraemos estrictamente min_mat y max_mat
                            $res['limites'][(int) $data['id']] = [
                                'min' => isset($data['min_mat']) ? (int) $data['min_mat'] : 0,
                                'max' => isset($data['max_mat']) ? (int) $data['max_mat'] : 999,
                                'nombre_original' => $data['nombre'] ?? '',
                                'nombre_display' => trim(explode(':', mb_convert_encoding($data['nombre'] ?? '', 'UTF-8', 'UTF-8'))[0])
                            ];
                        }
                    }
                }
            } else {
                Log::warning("Respuesta no exitosa API Facturacion (Alumno {$alumnoId}, Inst {$institucionId}): HTTP {$httpCode}");
                $res['fallida'] = true;
            }
        } catch (\Exception $e) {
            Log::error("Fallo API Externa (Alumno {$alumnoId}, Inst {$institucionId}): " . $e->getMessage());
            $res['fallida'] = true;
        }

        return $res;
    }

    /**
     * Motor de búsqueda que cruza ID o hace un fallback estricto ignorando encoding corrupto
     */
    private function buscarLimitesDelPlan($plan, $limitesExternos)
    {
        // 1. Intento por ID 
        $idAComparar = $plan->ID; 

        if (isset($limitesExternos[$idAComparar])) {
            return $limitesExternos[$idAComparar];
        }

        // 2. Fallback agresivo: Quitamos vocales, espacios, comas y el "?" corrupto para forzar match de consonantes
        $limpiarString = function($str) {
            $str = mb_strtolower(trim($str), 'UTF-8');
            $str = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'u', 'n'], $str);
            return preg_replace('/[^bcdfghjklmnpqrstvwxyz0-9]/', '', $str);
        };

        $nombrePlanLimpio = $limpiarString($plan->Nombre);

        if (!empty($nombrePlanLimpio)) {
            foreach ($limitesExternos as $idApi => $lim) {
                $nombreApiLimpio = $limpiarString($lim['nombre_original']);
                
                // Si la raíz de texto "prfsrdntlg" está contenida
                if (strpos($nombreApiLimpio, $nombrePlanLimpio) !== false || strpos($nombrePlanLimpio, $nombreApiLimpio) !== false) {
                    return $lim;
                }
            }
        }

        return null; // No hay límites detectados para este plan (se asume ilimitado)
    }

    public function getMateriasDisponibles(int $institucionId, int $alumnoId)
    {
        $alumno = Alumno::with(['curso.plan'])->find($alumnoId);
        
        if (!$alumno) throw new Exception("Alumno no encontrado.");
        $ciclo = DB::table('ciclo_lectivo')->where('ID_Nivel', $alumno->ID_Nivel)->where('Vigente', 'SI')->first();
        if (!$ciclo) throw new Exception("No hay ciclo lectivo vigente configurado.");

       // Obtenemos límites para TODAS las instituciones
        $apiData = $this->obtenerLimitesExternos($institucionId, $alumnoId);
        $limitesExternos = $apiData['limites'];
        $apiFallida = $apiData['fallida'];

        // --- INICIO MODIFICACIÓN (GRACEFUL DEGRADATION) ---
        $cursosInscriptos = [];

        // 2. Comprobamos si la tabla existe antes de consultarla
        if (Schema::hasTable('inscripciones')) {
            $cursosInscriptos = DB::table('inscripciones')
                ->where('ID_Alumno', $alumnoId)
                ->where('Estado', 1) // Asumimos 1 como activo/vigente
                ->pluck('ID_Curso')
                ->toArray();
        }
        // Si no existe, $cursosInscriptos queda como un array vacío y el sistema no colapsa.

        // Asegurar que el curso principal de la tabla alumnos esté incluido
        if ($alumno->ID_Curso && !in_array($alumno->ID_Curso, $cursosInscriptos)) {
            $cursosInscriptos[] = $alumno->ID_Curso;
        }

        $planesIds = [];
        
        // Mapear esos cursos a sus planes correspondientes
        if (!empty($cursosInscriptos)) {
            $cursosInfo = DB::table('planes_estudio_cursos')->whereIn('ID', $cursosInscriptos)->get();
            foreach ($cursosInfo as $cInfo) {
                // Si está inscripto en varios cursos del mismo plan, conservamos el de mayor orden
                if (!isset($planesIds[$cInfo->ID_Plan]) || $cInfo->Orden > $planesIds[$cInfo->ID_Plan]) {
                    $planesIds[$cInfo->ID_Plan] = $cInfo->Orden;
                }
            }
        }
        // --- FIN MODIFICACIÓN ---

        $inscripcionesCiclo = $this->getInscripcionesActuales($alumnoId, $ciclo);
        $idsGruposInscriptos = $inscripcionesCiclo->pluck('ID_Materia_Grupal')->toArray();

        // 2. Otros planes donde ya tenga inscripciones previas en este ciclo
        foreach ($inscripcionesCiclo as $insc) {
            if (!isset($planesIds[$insc->ID_Plan])) {
                $planesIds[$insc->ID_Plan] = 999; 
            }
        }
        $turnoAlumno = $this->obtenerTurnoAlumno($alumno, $ciclo);
        $notasAlumno = DB::table('notas_cursada')->where('ID_Alumno', $alumnoId)->get();

        $dataResponse = [];

        foreach ($planesIds as $planId => $ordenPlan) {
            $plan = DB::table('planes_estudio')->where('ID', $planId)->first();
            if (!$plan) continue;
            
            $tieneLimite = false;
            $limiteMinPermitido = 0;
            $limiteMaxPermitido = 999;
            $actividadNombre = trim($plan->Nombre);

            if ($apiFallida) {
                $tieneLimite = true;
                $limiteMaxPermitido = 0;
                $actividadNombre = "Error: Sin configuración de matrícula externa";
            } else {
                $limitesAsignados = $this->buscarLimitesDelPlan($plan, $limitesExternos);
                
                if ($limitesAsignados) {
                    $tieneLimite = true;
                    $limiteMinPermitido = $limitesAsignados['min']; 
                    $limiteMaxPermitido = $limitesAsignados['max']; 
                    
                    $nombreDisplay = str_replace('?', 'í', $limitesAsignados['nombre_display']);
                    $actividadNombre = !empty($nombreDisplay) ? $nombreDisplay : trim($plan->Nombre);
                }
                // Si $limitesAsignados es null, asume que no hay límites (999).
            }

            $yaAnotadasPlan = $inscripcionesCiclo->where('ID_Plan', $planId)->count();
            $disponiblesGlobales = max(0, $limiteMaxPermitido - $yaAnotadasPlan);

            $gruposPlan = DB::table('materias_grupales as mg')
                ->join('materias as m', 'mg.ID_Materia', '=', 'm.ID')
                ->join('materias_planes as mp', 'm.ID_Materia_Plan', '=', 'mp.ID')
                ->join('planes_estudio_cursos as pec', 'mp.Curso', '=', 'pec.ID')
                ->leftJoin('personal as p', 'mg.ID_Personal', '=', 'p.ID')
                ->where('mp.ID_Plan', $planId)
                ->where('mg.ID_Ciclo_Lectivo', optional($ciclo)->ID)
                ->where('mg.AI', 'SI')
                ->select(
                    'mg.*', 
                    'm.ID as Materia_ID', 
                    'm.ID_Curso as Materia_ID_Curso',
                    'mp.ID as MPlan_ID', 
                    'pec.ID as Curso_ID', 
                    'pec.Curso as CursoNombre', 
                    'pec.Orden as CursoOrden',
                    'p.Apellido as DocenteApellido'
                )
                ->get();

            $cursosAgrupados = [];
            foreach ($gruposPlan as $grupo) {
                if ($ordenPlan != 999 && $grupo->CursoOrden > $ordenPlan) continue;
                if ($ordenPlan != 999 && $ordenPlan == $grupo->CursoOrden) {
                    // Validamos contra el array multidimensional de cursos inscriptos
                    if (!in_array($grupo->Materia_ID_Curso, $cursosInscriptos)) continue;
                }

                $yaAprobada = $notasAlumno->where('ID_Materia', $grupo->Materia_ID)->filter(function($n){ 
                    return $n->Final == 'SI' || $n->Cursada == 1; 
                })->isNotEmpty();
                
                if ($yaAprobada) continue;

                $motivos = [];
                $inscripto = in_array($grupo->ID, $idsGruposInscriptos);

                if ($tieneLimite && $limiteMaxPermitido != 999 && $disponiblesGlobales <= 0 && !$inscripto) {
                    $motivos[] = "Límite de $limiteMaxPermitido materias alcanzado para esta cursada.";
                }
                if ($alumno->ID_Situacion != 2) $motivos[] = "Estado de alumno no habilitado.";
                if (!empty($turnoAlumno) && $turnoAlumno !== $grupo->Turno) $motivos[] = "Turno no coincidente.";
                
                $evalCorr = $this->validarCorrelativas($grupo->MPlan_ID, $alumnoId);
                if (!$evalCorr['autorizado']) $motivos[] = $evalCorr['mensaje'];

                if (!isset($cursosAgrupados[$grupo->CursoNombre])) {
                    $cursosAgrupados[$grupo->CursoNombre] = [];
                }

                $cursosAgrupados[$grupo->CursoNombre][] = [
                    'id_materia_grupal' => $grupo->ID,
                    'materia' => trim($grupo->Materia),
                    'docente' => $grupo->DocenteApellido ?? 'A designar',
                    'estado' => $inscripto ? 'INSCRIPTO' : (empty($motivos) ? 'DISPONIBLE' : 'BLOQUEADA'),
                    'motivos_bloqueo' => $inscripto ? "Ya estás anotado." : implode(' ', $motivos),
                    'permite_inscripcion' => (empty($motivos) && !$inscripto)
                ];
            }

            $secciones = [];
            $cursosBase = DB::table('planes_estudio_cursos')->where('ID_Plan', $planId)
                ->where('Orden', '<=', ($ordenPlan == 999 ? 999 : $ordenPlan))
                ->orderBy('Orden')->get();

            foreach ($cursosBase as $cB) {
                $materiasDeSeccion = $cursosAgrupados[$cB->Curso] ?? [];
                
                $secciones[] = [
                    'curso_nombre' => $cB->Curso,
                    'inscripciones_disponibles' => (!$tieneLimite || $limiteMaxPermitido == 999) ? 'Ilimitadas' : $disponiblesGlobales,
                    'materias' => $materiasDeSeccion
                ];
            }

            $dataResponse[] = [
                'actividad_nombre' => $actividadNombre,
                'limites' => $tieneLimite ? [
                    'min' => $limiteMinPermitido,
                    'max' => ($limiteMaxPermitido == 999) ? null : $limiteMaxPermitido
                ] : null,
                'secciones' => $secciones
            ];
        }

        return [
            'success' => true,
            'data' => $dataResponse,
            'insc_disponibles' => 0, 
            'message' => 'Grupos disponibles obtenidos correctamente.'
        ];
    }

    public function inscribir(int $institucionId, int $alumnoId, int $idMateriaGrupal)
    {
        $materiaInfo = DB::table('materias_grupales')
            ->join('materias', 'materias_grupales.ID_Materia', '=', 'materias.ID')
            ->join('materias_planes', 'materias.ID_Materia_Plan', '=', 'materias_planes.ID')
            ->where('materias_grupales.ID', $idMateriaGrupal)
            ->select('materias.ID_Materia_Plan', 'materias_planes.ID_Plan')
            ->first();

        if (!$materiaInfo) throw new \Exception('La materia grupal solicitada no es válida.');

        $apiData = $this->obtenerLimitesExternos($institucionId, $alumnoId);
        $limitesExternos = $apiData['limites'];
        
        $tieneLimite = false;
        $limiteMaxPermitido = 999;

        if ($apiData['fallida']) {
            throw new \Exception("Error al consultar el servicio de matriculación externa. Intente más tarde.");
        }
        
        $plan = DB::table('planes_estudio')->where('ID', $materiaInfo->ID_Plan)->first();
        $limitesAsignados = $plan ? $this->buscarLimitesDelPlan($plan, $limitesExternos) : null;

        if ($limitesAsignados) {
            $tieneLimite = true;
            $limiteMaxPermitido = $limitesAsignados['max'];
        }

        return DB::transaction(function () use ($alumnoId, $idMateriaGrupal, $materiaInfo, $tieneLimite, $limiteMaxPermitido) {
            $alumno = DB::table('alumnos')->where('ID', $alumnoId)->first();
            if (!$alumno) throw new \Exception('Alumno no encontrado.');

            $parametros = DB::table('nivel_parametros')->where('ID_Nivel', $alumno->ID_Nivel)->first();
            if (!$parametros || $parametros->HabAI != 1) {
                throw new \Exception('El período de inscripciones no se encuentra abierto.');
            }

            $ciclo = DB::table('ciclo_lectivo')->where('ID_Nivel', $alumno->ID_Nivel)->where('Vigente', 'SI')->first();

            if ($tieneLimite && $limiteMaxPermitido != 999) {
                $cantidadInscriptasPlan = DB::table('grupos as g')
                    ->join('materias_grupales as mg', 'g.ID_Materia_Grupal', '=', 'mg.ID')
                    ->join('materias as m', 'mg.ID_Materia', '=', 'm.ID')
                    ->join('materias_planes as mp', 'm.ID_Materia_Plan', '=', 'mp.ID')
                    ->where('g.ID_Alumno', $alumnoId)
                    ->where('g.ID_Ciclo_Lectivo', optional($ciclo)->ID)
                    ->where('mp.ID_Plan', $materiaInfo->ID_Plan)
                    ->count();

                if ($cantidadInscriptasPlan >= $limiteMaxPermitido) {
                    throw new \Exception("Has alcanzado el límite máximo de materias permitidas ({$limiteMaxPermitido}) para esta cursada en específico.");
                }
            }

            $yaInscriptoEnPlan = DB::table('grupos')
                ->join('materias_grupales', 'grupos.ID_Materia_Grupal', '=', 'materias_grupales.ID')
                ->join('materias', 'materias_grupales.ID_Materia', '=', 'materias.ID')
                ->where('grupos.ID_Alumno', $alumnoId)
                ->where('grupos.ID_Ciclo_Lectivo', optional($ciclo)->ID)
                ->where('materias.ID_Materia_Plan', $materiaInfo->ID_Materia_Plan)
                ->exists();

            if ($yaInscriptoEnPlan) throw new \Exception('Ya te encontrás inscripto en esta materia.');

            $grupo = DB::table('materias_grupales')->where('ID', $idMateriaGrupal)->lockForUpdate()->first();
            if (!$grupo) throw new \Exception('El grupo no existe.');

            if ($grupo->Cupo > 0 && $grupo->Alumnos >= $grupo->Cupo) {
                throw new \Exception('El grupo se encuentra completo.');
            }

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

    public function darDeBaja(int $alumnoId, int $idMateriaGrupal)
    {
        return DB::transaction(function () use ($alumnoId, $idMateriaGrupal) {
            $alumno = DB::table('alumnos')->where('ID', $alumnoId)->first();
            $parametros = DB::table('nivel_parametros')->where('ID_Nivel', $alumno->ID_Nivel)->first();

            if (!$parametros || $parametros->HabAI != 1) {
                throw new \Exception('El período de inscripciones se encuentra cerrado. No podés cancelar la reserva.');
            }

            $eliminado = DB::table('grupos')
                ->where('ID_Alumno', $alumnoId)
                ->where('ID_Materia_Grupal', $idMateriaGrupal)
                ->delete();

            if (!$eliminado) {
                throw new \Exception('No se encontró una inscripción vigente para cancelar.');
            }

            DB::table('materias_grupales')->where('ID', $idMateriaGrupal)->decrement('Alumnos');

            return true;
        });
    }

    private function getInscripcionesActuales($alumnoId, $ciclo)
    {
        return DB::table('grupos as g')
            ->join('materias_grupales as mg', 'g.ID_Materia_Grupal', '=', 'mg.ID')
            ->join('materias as m', 'mg.ID_Materia', '=', 'm.ID')
            ->join('materias_planes as mp', 'm.ID_Materia_Plan', '=', 'mp.ID')
            ->where('g.ID_Alumno', $alumnoId)
            ->where('g.ID_Ciclo_Lectivo', optional($ciclo)->ID)
            ->select('mp.Curso as ID_Curso_Plan', 'mp.ID_Plan', 'g.ID_Materia_Grupal')->get();
    }

    private function validarCorrelativas($mPlanId, $alumnoId)
    {
        $correlativas = DB::table('planes_estudio_correlativas_cursada')->where('ID_Materia', $mPlanId)->where('B', 0)->get();
        foreach ($correlativas as $c) {
            $instancias = DB::table('materias')->where('ID_Materia_Plan', $c->ID_Materia_C)->pluck('ID')->toArray();
            if (empty($instancias)) continue;

            $cumple = DB::table('notas_cursada')->where('ID_Alumno', $alumnoId)->whereIn('ID_Materia', $instancias)
                ->where(function($q) use ($c) {
                    $c->Tipo == 1 ? $q->where('Cursada', 1) : $q->where('Final', 'SI');
                })->exists();
                
            if (!$cumple) {
                $nom = DB::table('materias_planes')->where('ID', $c->ID_Materia_C)->value('Materia');
                return ['autorizado' => false, 'mensaje' => "Falta " . ($c->Tipo == 1 ? 'Cursada' : 'Final') . " de $nom."];
            }
        }
        return ['autorizado' => true];
    }

    private function obtenerTurnoAlumno($alumno, $ciclo)
    {
        if (!empty($alumno->Turno)) return $alumno->Turno;
        return DB::table('grupos as g')->join('materias_grupales as mg', 'g.ID_Materia_Grupal', '=', 'mg.ID')
            ->where('g.ID_Alumno', $alumno->ID)->where('g.ID_Ciclo_Lectivo', '<', 4)
            ->orderBy('g.ID', 'desc')->value('mg.Turno');
    }
}