<?php

namespace App\Repositories\AppFamilias;

use App\Services\DatabaseManager;
use App\Models\Alumno;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Agrupacion;
use App\Models\Grupo;
use App\Models\TareaAdjunto;
use App\Models\TareaDevolucionAdjunto;
use App\Models\TareaEnvio;
use App\Models\TareaResolucion;
use App\Models\TaskSubmission;
use App\Models\TareaVirtual;
use App\Models\TareaResolucionAdjunto;
use App\Models\TareaVirtualVencimiento;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FamilyAulaRepository
{
    /**
     * @var DatabaseManager
     */
    protected $DatabaseManager;
    public function __construct(DatabaseManager $DatabaseManager)
    {
        $this->DatabaseManager = $DatabaseManager;
    }

    public function getAulasDisponibles($studentId, $cicloLectivo = null)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) {
            return null;
        }

        $hoy = Carbon::now()->format('Y-m-d');

        $cicloQuery = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel);

        if (!empty($cicloLectivo)) {
            $ciclo = (clone $cicloQuery)
                ->where('Ciclo_lectivo', (int) $cicloLectivo)
                ->first();
        } else {
            $ciclo = null;
        }

        if (!$ciclo) {
            $ciclo = (clone $cicloQuery)
                ->where('Vigente', 'SI')
                ->orderByDesc('ID')
                ->first();
        }

        if (!$ciclo) {
            $ciclo = (clone $cicloQuery)
                ->orderByDesc('ID')
                ->first();
        }

        if (!$ciclo) {
            return null;
        }

        $cicloId = (int) $ciclo->ID;
        $fechaInicioCiclo = !empty($ciclo->IPT) ? $ciclo->IPT : '2000-01-01';
        $idNivel = (int) $alumno->ID_Nivel;

        $param = DB::table('institucion_parametros')
            ->where('ID', 1)
            ->first();

        $tipoAula = (int) ($param->Aula_Virtual ?? 1);
        $urlAulaBase = (string) ($param->URL_Aula_Virtual ?? '');

        $materiasCurso = collect();

        // Igual que profesor:
        // si el nivel es 2, NO listamos curriculares para evitar duplicados con grupales/comisiones.
        if ($idNivel !== 2) {
            $materiasCurso = DB::table('materias as m')
                ->leftJoin('personal as p', 'm.ID_Personal', '=', 'p.ID')
                ->leftJoin('cursos as c', 'm.ID_Curso', '=', 'c.ID')
                ->where('m.ID_Curso', $alumno->ID_Curso)
                ->orderBy('m.Materia', 'asc')
                ->select(
                    'm.ID',
                    'm.Materia',
                    'm.ID_Course_Moodle',
                    'm.ID_Curso',
                    'c.Cursos',
                    'p.Apellido',
                    'p.Nombre'
                )
                ->get()
                ->map(function ($item) use ($studentId, $cicloId, $fechaInicioCiclo, $hoy, $tipoAula, $urlAulaBase, $ciclo) {
                    $totalRecursos = DB::table('clases_virtuales_contenidos as cvc')
                        ->where('cvc.ID_Materia', $item->ID)
                        ->where('cvc.Tipo_Materia', 'c')
                        ->where('cvc.ID_Clase', 0)
                        ->where('cvc.Visible', 1)
                        ->where('cvc.Estado', '<=', 1)
                        ->where('cvc.Fecha', '>=', $fechaInicioCiclo)
                        ->where('cvc.Fecha', '<=', $hoy)
                        ->where(function ($q) use ($hoy) {
                            $q->whereNull('cvc.Fecha_Vencimiento')
                                ->orWhere('cvc.Fecha_Vencimiento', '0000-00-00')
                                ->orWhere('cvc.Fecha_Vencimiento', '>=', $hoy);
                        })
                        ->count();

                    $totalClases = DB::table('clases_virtuales as cv')
                        ->join('clases_virtuales_envios as cve', function ($join) use ($studentId) {
                            $join->on('cve.ID_Clase', '=', 'cv.ID')
                                ->where('cve.ID_Destinatario', '=', $studentId)
                                ->where('cve.Envio', '=', 1);
                        })
                        ->where('cv.ID_Materia', $item->ID)
                        ->where('cv.Tipo_Materia', 'c')
                        ->where('cv.ID_Ciclo_Lectivo', $cicloId)
                        ->where('cv.Estado', 1)
                        ->where('cv.Fecha_Publicacion', '<=', date('Y-m-d'))
                        ->distinct('cv.ID')
                        ->count('cv.ID');

                    $totalTareas = DB::table('tareas_virtuales as tv')
                        ->join('tareas_envios as te', function ($join) use ($studentId) {
                            $join->on('te.ID_Tarea', '=', 'tv.ID')
                                ->where('te.ID_Destinatario', '=', $studentId)
                                ->where('te.Envio', '=', 1);
                        })
                        ->where('tv.ID_Materia', $item->ID)
                        ->where('tv.Tipo_Materia', 'c')
                        ->where('tv.ID_Ciclo_Lectivo', $cicloId)
                        ->where('tv.ID_Clase', 0)
                        ->where('tv.Cerrada', 0)
                        ->where('tv.Envio', 1)
                        ->where('tv.Tipo', 1)
                        ->where(function ($q) use ($hoy) {
                            $q->whereNull('tv.Fecha_Publicacion')
                                ->orWhere('tv.Fecha_Publicacion', '<=', $hoy);
                        })
                        ->distinct('tv.ID')
                        ->count('tv.ID');

                    $totalForos = DB::table('tareas_virtuales as tv')
                        ->join('tareas_envios as te', function ($join) use ($studentId) {
                            $join->on('te.ID_Tarea', '=', 'tv.ID')
                                ->where('te.ID_Destinatario', '=', $studentId)
                                ->where('te.Envio', '=', 1);
                        })
                        ->where('tv.ID_Materia', $item->ID)
                        ->where('tv.Tipo_Materia', 'c')
                        ->where('tv.ID_Ciclo_Lectivo', $cicloId)
                        ->where('tv.ID_Clase', 0)
                        ->where('tv.Cerrada', 0)
                        ->where('tv.Envio', 1)
                        ->where('tv.Tipo', 2)
                        ->where(function ($q) use ($hoy) {
                            $q->whereNull('tv.Fecha_Publicacion')
                                ->orWhere('tv.Fecha_Publicacion', '<=', $hoy);
                        })
                        ->distinct('tv.ID')
                        ->count('tv.ID');

                    $urlAula = '';
                    if ($tipoAula !== 1 && $urlAulaBase !== '' && (int) ($item->ID_Course_Moodle ?? 0) > 0) {
                        $urlAula = rtrim($urlAulaBase, '/') . '/course/view.php?id=' . (int) $item->ID_Course_Moodle;
                    }

                    $cursoNombre = trim((string) ($item->Cursos ?? ''));
                    $materiaLabel = trim((string) $item->Materia);
                    if ($cursoNombre !== '') {
                        $materiaLabel .= ' (' . $cursoNombre . ')';
                    }

                    return [
                        'id_materia' => (int) $item->ID,
                        'tipo_materia' => 'C',
                        'materia' => $materiaLabel,
                        'docente' => trim((string) ($item->Apellido ?? '') . ', ' . (string) ($item->Nombre ?? ''), ', '),
                        'ciclo_lectivo' => $cicloId,
                        'nombre_ciclo_lectivo' => (string) ($ciclo->Ciclo_lectivo ?? ''),
                        'actual' => ((string) ($ciclo->Vigente ?? '') === 'SI') ? 1 : 0,
                        'tipo_aula' => $tipoAula,
                        'url_aula' => $urlAula,
                        'total_recursos' => (int) $totalRecursos,
                        'total_clases' => (int) $totalClases,
                        'total_tareas' => (int) $totalTareas,
                        'total_foros' => (int) $totalForos,
                        'novedades_total' => $this->countNovedades($studentId, $item->ID, 'c', $cicloId, $fechaInicioCiclo, $hoy),
                    ];
                });
        }

        $materiasGrupales = DB::table('materias_grupales as mg')
            ->join('grupos as g', function ($join) use ($studentId, $cicloId) {
                $join->on('mg.ID', '=', 'g.ID_Materia_Grupal')
                    ->where('g.ID_Alumno', '=', $studentId)
                    ->where('g.ID_Ciclo_Lectivo', '=', $cicloId);
            })
            ->leftJoin('personal as p', 'mg.ID_Personal', '=', 'p.ID')
            ->leftJoin('materias as m', 'mg.ID_Materia', '=', 'm.ID')
            ->where('mg.Estado', 0)
            ->orderBy('mg.Materia', 'asc')
            ->select(
                'mg.ID',
                'mg.Materia',
                'mg.ID_Ciclo_Lectivo',
                'p.Apellido',
                'p.Nombre',
                'm.ID_Course_Moodle'
            )
            ->distinct()
            ->get()
            ->map(function ($item) use ($studentId, $fechaInicioCiclo, $hoy, $tipoAula, $urlAulaBase, $cicloId, $ciclo) {
                $totalRecursos = DB::table('clases_virtuales_contenidos as cvc')
                    ->where('cvc.ID_Materia', $item->ID)
                    ->where('cvc.Tipo_Materia', 'g')
                    ->where('cvc.ID_Clase', 0)
                    ->where('cvc.Visible', 1)
                    ->where('cvc.Estado', '<=', 1)
                    ->where('cvc.Fecha', '>=', $fechaInicioCiclo)
                    ->where('cvc.Fecha', '<=', $hoy)
                    ->where(function ($q) use ($hoy) {
                        $q->whereNull('cvc.Fecha_Vencimiento')
                            ->orWhere('cvc.Fecha_Vencimiento', '0000-00-00')
                            ->orWhere('cvc.Fecha_Vencimiento', '>=', $hoy);
                    })
                    ->count();

                $totalClases = DB::table('clases_virtuales as cv')
                    ->join('clases_virtuales_envios as cve', function ($join) use ($studentId) {
                        $join->on('cve.ID_Clase', '=', 'cv.ID')
                            ->where('cve.ID_Destinatario', '=', $studentId)
                            ->where('cve.Envio', '=', 1);
                    })
                    ->where('cv.ID_Materia', $item->ID)
                    ->where('cv.Tipo_Materia', 'g')
                    ->where('cv.ID_Ciclo_Lectivo', $cicloId)
                    ->where('cv.Estado', 1)
                    ->where('cv.Fecha_Publicacion', '<=', date('Y-m-d'))
                    ->distinct('cv.ID')
                    ->count('cv.ID');

                $totalTareas = DB::table('tareas_virtuales as tv')
                    ->join('tareas_envios as te', function ($join) use ($studentId) {
                        $join->on('te.ID_Tarea', '=', 'tv.ID')
                            ->where('te.ID_Destinatario', '=', $studentId)
                            ->where('te.Envio', '=', 1);
                    })
                    ->where('tv.ID_Materia', $item->ID)
                    ->where('tv.Tipo_Materia', 'g')
                    ->where('tv.ID_Ciclo_Lectivo', $cicloId)
                    ->where('tv.ID_Clase', 0)
                    ->where('tv.Cerrada', 0)
                    ->where('tv.Envio', 1)
                    ->where('tv.Tipo', 1)
                    ->where(function ($q) use ($hoy) {
                        $q->whereNull('tv.Fecha_Publicacion')
                            ->orWhere('tv.Fecha_Publicacion', '<=', $hoy);
                    })
                    ->distinct('tv.ID')
                    ->count('tv.ID');

                $totalForos = DB::table('tareas_virtuales as tv')
                    ->join('tareas_envios as te', function ($join) use ($studentId) {
                        $join->on('te.ID_Tarea', '=', 'tv.ID')
                            ->where('te.ID_Destinatario', '=', $studentId)
                            ->where('te.Envio', '=', 1);
                    })
                    ->where('tv.ID_Materia', $item->ID)
                    ->where('tv.Tipo_Materia', 'g')
                    ->where('tv.ID_Ciclo_Lectivo', $cicloId)
                    ->where('tv.ID_Clase', 0)
                    ->where('tv.Cerrada', 0)
                    ->where('tv.Envio', 1)
                    ->where('tv.Tipo', 2)
                    ->where(function ($q) use ($hoy) {
                        $q->whereNull('tv.Fecha_Publicacion')
                            ->orWhere('tv.Fecha_Publicacion', '<=', $hoy);
                    })
                    ->distinct('tv.ID')
                    ->count('tv.ID');

                $urlAula = '';
                if ($tipoAula !== 1 && $urlAulaBase !== '' && (int) ($item->ID_Course_Moodle ?? 0) > 0) {
                    $urlAula = rtrim($urlAulaBase, '/') . '/course/view.php?id=' . (int) $item->ID_Course_Moodle;
                }

                return [
                    'id_materia' => (int) $item->ID,
                    'tipo_materia' => 'G',
                    'materia' => trim((string) $item->Materia),
                    'docente' => trim((string) ($item->Apellido ?? '') . ', ' . (string) ($item->Nombre ?? ''), ', '),
                    'ciclo_lectivo' => (int) ($item->ID_Ciclo_Lectivo ?? $cicloId),
                    'nombre_ciclo_lectivo' => (string) ($ciclo->Ciclo_lectivo ?? ''),
                    'actual' => ((int) ($item->ID_Ciclo_Lectivo ?? $cicloId) === $cicloId) ? 1 : 0,
                    'tipo_aula' => $tipoAula,
                    'url_aula' => $urlAula,
                    'total_recursos' => (int) $totalRecursos,
                    'total_clases' => (int) $totalClases,
                    'total_tareas' => (int) $totalTareas,
                    'total_foros' => (int) $totalForos,
                    'novedades_total' => $this->countNovedades($studentId, $item->ID, 'g', $cicloId, $fechaInicioCiclo, $hoy),
                ];
            });

        return $materiasCurso
            ->merge($materiasGrupales)
            ->sortBy('materia')
            ->values()
            ->all();
    }

    public function getDetalleAula($studentId, $materiaId, $tipoMateria, $cicloLectivo = null)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) {
            return null;
        }

        $tipoMateria = strtolower(trim((string) $tipoMateria));
        if (!in_array($tipoMateria, ['c', 'g'], true)) {
            return null;
        }

        $hoy = Carbon::now()->format('Y-m-d');
        $ahoraFecha = Carbon::now()->format('Y-m-d');
        $ahoraHora = Carbon::now()->format('H:i:s');

        $cicloQuery = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel);

        if (!empty($cicloLectivo)) {
            $ciclo = (clone $cicloQuery)
                ->where('Ciclo_lectivo', (int) $cicloLectivo)
                ->first();
        } else {
            $ciclo = null;
        }

        if (!$ciclo) {
            $ciclo = (clone $cicloQuery)
                ->where('Vigente', 'SI')
                ->orderByDesc('ID')
                ->first();
        }

        if (!$ciclo) {
            $ciclo = (clone $cicloQuery)->orderByDesc('ID')->first();
        }

        if (!$ciclo) {
            return null;
        }

        $cicloId = (int) $ciclo->ID;
        $fechaInicioCiclo = !empty($ciclo->IPT) ? $ciclo->IPT : '2000-01-01';

        if ($tipoMateria === 'c') {
            $materiaRow = DB::table('materias as m')
                ->leftJoin('cursos as c', 'm.ID_Curso', '=', 'c.ID')
                ->leftJoin('personal as p', 'm.ID_Personal', '=', 'p.ID')
                ->where('m.ID', $materiaId)
                ->select(
                    'm.ID',
                    'm.Materia',
                    'm.ID_Curso',
                    'c.Cursos',
                    'p.Apellido',
                    'p.Nombre'
                )
                ->first();

            if (!$materiaRow) {
                return null;
            }

            $materiaLabel = trim((string) $materiaRow->Materia);
            if (!empty($materiaRow->Cursos)) {
                $materiaLabel .= ' (' . trim((string) $materiaRow->Cursos) . ')';
            }
        } else {
            $materiaRow = DB::table('materias_grupales as mg')
                ->leftJoin('personal as p', 'mg.ID_Personal', '=', 'p.ID')
                ->where('mg.ID', $materiaId)
                ->select(
                    'mg.ID',
                    'mg.Materia',
                    'mg.ID_Ciclo_Lectivo',
                    'p.Apellido',
                    'p.Nombre'
                )
                ->first();

            if (!$materiaRow) {
                return null;
            }

            $materiaLabel = trim((string) $materiaRow->Materia);
        }

        $totalRecursos = DB::table('clases_virtuales_contenidos as cvc')
            ->where('cvc.ID_Materia', $materiaId)
            ->where('cvc.Tipo_Materia', $tipoMateria)
            ->where('cvc.ID_Clase', 0)
            ->where('cvc.Visible', 1)
            ->where('cvc.Estado', '<=', 1)
            ->where('cvc.Fecha', '>=', $fechaInicioCiclo)
            ->where('cvc.Fecha', '<=', $hoy)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('cvc.Fecha_Vencimiento')
                    ->orWhere('cvc.Fecha_Vencimiento', '0000-00-00')
                    ->orWhere('cvc.Fecha_Vencimiento', '>=', $hoy);
            })
            ->count();

        $totalClases = DB::table('clases_virtuales as cv')
            ->join('clases_virtuales_envios as cve', function ($join) use ($studentId) {
                $join->on('cve.ID_Clase', '=', 'cv.ID')
                    ->where('cve.ID_Destinatario', '=', $studentId)
                    ->where('cve.Envio', '=', 1);
            })
            ->where('cv.ID_Materia', $materiaId)
            ->where('cv.Tipo_Materia', $tipoMateria)
            ->where('cv.ID_Ciclo_Lectivo', $cicloId)
            ->where('cv.Estado', 1)
            ->where('cv.Fecha_Publicacion', '<=', $hoy)
            ->distinct('cv.ID')
            ->count('cv.ID');

        $totalTareas = DB::table('tareas_virtuales as tv')
            ->join('tareas_envios as te', function ($join) use ($studentId) {
                $join->on('te.ID_Tarea', '=', 'tv.ID')
                    ->where('te.ID_Destinatario', '=', $studentId)
                    ->where('te.Envio', '=', 1);
            })
            ->where('tv.ID_Materia', $materiaId)
            ->where('tv.Tipo_Materia', $tipoMateria)
            ->where('tv.ID_Ciclo_Lectivo', $cicloId)
            ->where('tv.Cerrada', 0)
            ->where('tv.Envio', 1)
            ->where('tv.Tipo', 1)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('tv.Fecha_Publicacion')
                    ->orWhere('tv.Fecha_Publicacion', '<=', $hoy);
            })
            ->distinct('tv.ID')
            ->count('tv.ID');

        $totalForos = DB::table('tareas_virtuales as tv')
            ->join('tareas_envios as te', function ($join) use ($studentId) {
                $join->on('te.ID_Tarea', '=', 'tv.ID')
                    ->where('te.ID_Destinatario', '=', $studentId)
                    ->where('te.Envio', '=', 1);
            })
            ->where('tv.ID_Materia', $materiaId)
            ->where('tv.Tipo_Materia', $tipoMateria)
            ->where('tv.ID_Ciclo_Lectivo', $cicloId)
            ->where('tv.Cerrada', 0)
            ->where('tv.Envio', 1)
            ->where('tv.Tipo', 2)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('tv.Fecha_Publicacion')
                    ->orWhere('tv.Fecha_Publicacion', '<=', $hoy);
            })
            ->distinct('tv.ID')
            ->count('tv.ID');

        $recursosGenerales = DB::table('clases_virtuales_contenidos as cvc')
            ->join('clases_virtuales_contenidos_tipos as cvct', 'cvc.ID_Tipo', '=', 'cvct.ID')
            ->leftJoin('clases_virtuales_contenidos_lecturas as l', function ($join) use ($studentId) {
                $join->on('cvc.ID', '=', 'l.ID_Contenido')
                    ->where('l.ID_Alumno', '=', $studentId);
            })
            ->where('cvc.ID_Materia', $materiaId)
            ->where('cvc.Tipo_Materia', $tipoMateria)
            ->where('cvc.ID_Clase', 0)
            ->where('cvc.Visible', 1)
            ->where('cvc.Estado', '<=', 1)
            ->where('cvc.Fecha', '>=', $fechaInicioCiclo)
            ->where('cvc.Fecha', '<=', $hoy)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('cvc.Fecha_Vencimiento')
                    ->orWhere('cvc.Fecha_Vencimiento', '0000-00-00')
                    ->orWhere('cvc.Fecha_Vencimiento', '>=', $hoy);
            })
            ->orderByDesc('cvc.ID')
            ->select(
                'cvc.*',
                'cvct.Tipo as NombreTipo',
                'cvct.Enlace as TipoEsEnlace',
                'l.ID as ID_Lectura'
            )
            ->get()
            ->map(function ($r) {
                $archivo = (string) ($r->Archivo ?? '');
                $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
                $leido = !empty($r->ID_Lectura);

                $tipoRecurso = 'archivo';
                if ((int) ($r->TipoEsEnlace ?? 0) === 1 || !empty($r->Enlace)) {
                    $tipoRecurso = 'enlace';
                } elseif (in_array($extension, ['pdf'], true)) {
                    $tipoRecurso = 'pdf';
                } elseif (in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'webm'], true)) {
                    $tipoRecurso = 'video';
                }

                return [
                    'id_recurso' => (int) $r->ID,
                    'id_clase' => (int) $r->ID_Clase,
                    'id_tipo_recurso' => (int) $r->ID_Tipo,
                    'tipo_recurso' => (string) ($r->NombreTipo ?? $tipoRecurso),
                    'tipo_recurso_codigo' => $tipoRecurso,
                    'titulo' => (string) $r->Titulo,
                    'descripcion' => (string) $r->Descripcion,
                    'enlace' => (string) $r->Enlace,
                    'archivo' => $archivo,
                    'servidor' => (int) ($r->Servidor ?? 0),
                    'url_codigo' => (string) ($r->Code ?? ''),
                    'fecha' => !empty($r->Fecha) ? Carbon::parse($r->Fecha)->format('Y-m-d') : null,
                    'fecha_vencimiento' => (!empty($r->Fecha_Vencimiento) && $r->Fecha_Vencimiento !== '0000-00-00')
                        ? Carbon::parse($r->Fecha_Vencimiento)->format('Y-m-d')
                        : null,
                    'visible' => (int) $r->Visible,
                    'estado' => (int) $r->Estado,
                    'leido' => $leido,
                    'progreso' => $leido ? 100 : 0,
                ];
            })
            ->values()
            ->all();

        $tareasGenerales = DB::table('tareas_virtuales as tv')
            ->join('tareas_envios as te', function ($join) use ($studentId) {
                $join->on('te.ID_Tarea', '=', 'tv.ID')
                    ->where('te.ID_Destinatario', '=', $studentId)
                    ->where('te.Envio', '=', 1);
            })
            ->where('tv.ID_Materia', $materiaId)
            ->where('tv.Tipo_Materia', $tipoMateria)
            ->where('tv.ID_Clase', 0)
            ->where('tv.ID_Ciclo_Lectivo', $cicloId)
            ->where('tv.Cerrada', 0)
            ->where('tv.Envio', 1)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('tv.Fecha_Publicacion')
                    ->orWhere('tv.Fecha_Publicacion', '<=', $hoy);
            })
            ->orderBy('tv.Fecha', 'desc')
            ->select(
                'tv.*',
                'te.Leido as Leido_Envio',
                'te.Resuelto as Resuelto_Envio',
                'te.Corregido as Corregido_Envio'
            )
            ->get()
            ->map(function ($t) use ($studentId) {
                $resuelta = DB::table('tareas_resoluciones')
                    ->where('ID_Tarea', $t->ID)
                    ->where('ID_Alumno', $studentId)
                    ->first();

                $adjuntos = DB::table('tareas_adjuntos')
                    ->where('ID_Tarea', $t->ID)
                    ->orderBy('ID', 'asc')
                    ->get(['ID', 'Titulo', 'Archivo'])
                    ->map(function ($adj) {
                        return [
                            'id' => (int) $adj->ID,
                            'titulo' => (string) $adj->Titulo,
                            'archivo' => (string) $adj->Archivo,
                        ];
                    })
                    ->values()
                    ->all();

                $estadoPublicacion = ((int) $t->Cerrada === 0)
                    ? (((int) $t->Envio === 1) ? 'Publicada' : 'En producción')
                    : 'Oculta (Cerrada)';

                return [
                    'id_tarea' => (int) $t->ID,
                    'id_clase' => (int) $t->ID_Clase,
                    'tipo_id' => (int) $t->Tipo,
                    'tipo_tarea' => ((int) $t->Tipo === 1) ? 'Tarea' : 'Foro',
                    'titulo' => (string) $t->Titulo,
                    'consigna' => (string) $t->Consigna,
                    'estado' => $estadoPublicacion,
                    'fecha' => !empty($t->Fecha) ? Carbon::parse($t->Fecha)->format('Y-m-d') : null,
                    'fecha_publicacion' => (!empty($t->Fecha_Publicacion) && $t->Fecha_Publicacion !== '0000-00-00')
                        ? Carbon::parse($t->Fecha_Publicacion)->format('Y-m-d')
                        : null,
                    'hora_publicacion' => (string) ($t->Hora_Publicacion ?? ''),
                    'fecha_entrega' => (!empty($t->Fecha_Vencimiento) && $t->Fecha_Vencimiento !== '0000-00-00')
                        ? Carbon::parse($t->Fecha_Vencimiento)->format('Y-m-d')
                        : null,
                    'hora_entrega' => (string) ($t->Hora_Vencimiento ?? ''),
                    'dest_sel' => (int) ($t->Dest_Sel ?? 0),
                    'cerrada' => (int) ($t->Cerrada ?? 0),
                    'envio' => (int) ($t->Envio ?? 0),
                    'leido' => (bool) ($t->Leido_Envio ?? 0),
                    'resuelto' => (bool) ($t->Resuelto_Envio ?? 0),
                    'corregido' => (bool) ($t->Corregido_Envio ?? 0),
                    'tiene_resolucion' => $resuelta ? true : false,
                    'estado_resolucion' => $resuelta
                        ? (((int) ($resuelta->Correcion ?? 0) === 1) ? 'Evaluado' : 'Entregado')
                        : 'Pendiente',
                    'adjuntos' => $adjuntos,
                ];
            })
            ->values()
            ->all();

        $clases = DB::table('clases_virtuales as cv')
            ->join('clases_virtuales_envios as cve', function ($join) use ($studentId) {
                $join->on('cve.ID_Clase', '=', 'cv.ID')
                    ->where('cve.ID_Destinatario', '=', $studentId)
                    ->where('cve.Envio', '=', 1);
            })
            ->where('cv.ID_Materia', $materiaId)
            ->where('cv.Tipo_Materia', $tipoMateria)
            ->where('cv.ID_Ciclo_Lectivo', $cicloId)
            ->where('cv.Estado', 1)
            ->where('cv.Fecha_Publicacion', '<=', $hoy)
            ->orderBy('cv.Orden', 'desc')
            ->select('cv.*', 'cve.ID as ID_Envio', 'cve.Leido as Leido_Clase')
            ->get();

        $detalleClases = [];

        foreach ($clases as $clase) {
            DB::table('clases_virtuales_envios')
                ->where('ID_Clase', $clase->ID)
                ->where('ID_Destinatario', $studentId)
                ->where('Envio', 1)
                ->where('Leido', 0)
                ->update([
                    'Leido' => 1,
                    'Fecha_Leido' => $ahoraFecha,
                    'Hora_Leido' => $ahoraHora,
                ]);

            $contenidos = DB::table('clases_virtuales_contenidos as cvc')
                ->join('clases_virtuales_contenidos_tipos as cvct', 'cvc.ID_Tipo', '=', 'cvct.ID')
                ->leftJoin('clases_virtuales_contenidos_lecturas as l', function ($join) use ($studentId) {
                    $join->on('cvc.ID', '=', 'l.ID_Contenido')
                        ->where('l.ID_Alumno', '=', $studentId);
                })
                ->where('cvc.ID_Clase', $clase->ID)
                ->where('cvc.Visible', 1)
                ->where('cvc.Estado', '<=', 1)
                ->orderBy('cvc.Orden', 'asc')
                ->select('cvc.*', 'cvct.Tipo as NombreTipo', 'cvct.Enlace as TipoEsEnlace', 'l.ID as ID_Lectura')
                ->get()
                ->map(function ($c) {
                    $archivo = (string) ($c->Archivo ?? '');
                    $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
                    $leido = !empty($c->ID_Lectura);

                    $tipoRecurso = 'archivo';
                    if ((int) ($c->TipoEsEnlace ?? 0) === 1 || !empty($c->Enlace)) {
                        $tipoRecurso = 'enlace';
                    } elseif (in_array($extension, ['pdf'], true)) {
                        $tipoRecurso = 'pdf';
                    } elseif (in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'webm'], true)) {
                        $tipoRecurso = 'video';
                    }

                    return [
                        'id_recurso' => (int) $c->ID,
                        'titulo' => (string) $c->Titulo,
                        'descripcion' => (string) $c->Descripcion,
                        'archivo' => $archivo,
                        'enlace' => (string) $c->Enlace,
                        'id_tipo_recurso' => (int) $c->ID_Tipo,
                        'tipo_recurso' => (string) ($c->NombreTipo ?? $tipoRecurso),
                        'tipo_recurso_codigo' => $tipoRecurso,
                        'fecha' => !empty($c->Fecha) ? Carbon::parse($c->Fecha)->format('Y-m-d') : null,
                        'fecha_vencimiento' => (!empty($c->Fecha_Vencimiento) && $c->Fecha_Vencimiento !== '0000-00-00')
                            ? Carbon::parse($c->Fecha_Vencimiento)->format('Y-m-d')
                            : null,
                        'servidor' => (int) ($c->Servidor ?? 0),
                        'url_codigo' => (string) ($c->Code ?? ''),
                        'leido' => $leido,
                        'progreso' => $leido ? 100 : 0,
                    ];
                })
                ->values()
                ->all();

            $actividades = DB::table('clases_virtuales_actividades as cva')
                ->join('tareas_virtuales as tv', 'cva.ID_Tarea', '=', 'tv.ID')
                ->join('tareas_envios as te', function ($join) use ($studentId) {
                    $join->on('te.ID_Tarea', '=', 'tv.ID')
                        ->where('te.ID_Destinatario', '=', $studentId)
                        ->where('te.Envio', '=', 1);
                })
                ->where('cva.ID_Clase', $clase->ID)
                ->where('cva.Visible', 1)
                ->where('tv.Cerrada', 0)
                ->where('tv.Envio', 1)
                ->orderBy('cva.Orden', 'asc')
                ->select(
                    'cva.ID_Tipo as TipoActividad',
                    'cva.Orden as OrdenActividad',
                    'tv.*',
                    'te.Leido as Leido_Envio',
                    'te.Resuelto as Resuelto_Envio',
                    'te.Corregido as Corregido_Envio'
                )
                ->get()
                ->map(function ($a) use ($studentId) {
                    $resuelta = DB::table('tareas_resoluciones')
                        ->where('ID_Tarea', $a->ID)
                        ->where('ID_Alumno', $studentId)
                        ->first();

                    $adjuntos = DB::table('tareas_adjuntos')
                        ->where('ID_Tarea', $a->ID)
                        ->orderBy('ID', 'asc')
                        ->get(['ID', 'Titulo', 'Archivo'])
                        ->map(function ($adj) {
                            return [
                                'id' => (int) $adj->ID,
                                'titulo' => (string) $adj->Titulo,
                                'archivo' => (string) $adj->Archivo,
                            ];
                        })
                        ->values()
                        ->all();

                    $estadoPublicacion = ((int) $a->Cerrada === 0)
                        ? (((int) $a->Envio === 1) ? 'Publicada' : 'En producción')
                        : 'Oculta (Cerrada)';

                    return [
                        'id_tarea' => (int) $a->ID,
                        'orden' => (int) ($a->OrdenActividad ?? 0),
                        'tipo_id' => (int) $a->Tipo,
                        'tipo_tarea' => ((int) $a->Tipo === 1) ? 'Tarea' : 'Foro',
                        'titulo' => (string) $a->Titulo,
                        'consigna' => (string) $a->Consigna,
                        'estado' => $estadoPublicacion,
                        'fecha' => !empty($a->Fecha) ? Carbon::parse($a->Fecha)->format('Y-m-d') : null,
                        'fecha_publicacion' => (!empty($a->Fecha_Publicacion) && $a->Fecha_Publicacion !== '0000-00-00')
                            ? Carbon::parse($a->Fecha_Publicacion)->format('Y-m-d')
                            : null,
                        'hora_publicacion' => (string) ($a->Hora_Publicacion ?? ''),
                        'fecha_entrega' => (!empty($a->Fecha_Vencimiento) && $a->Fecha_Vencimiento !== '0000-00-00')
                            ? Carbon::parse($a->Fecha_Vencimiento)->format('Y-m-d')
                            : null,
                        'hora_entrega' => (string) ($a->Hora_Vencimiento ?? ''),
                        'leido' => (bool) ($a->Leido_Envio ?? 0),
                        'resuelto' => (bool) ($a->Resuelto_Envio ?? 0),
                        'corregido' => (bool) ($a->Corregido_Envio ?? 0),
                        'tiene_resolucion' => $resuelta ? true : false,
                        'estado_resolucion' => $resuelta
                            ? (((int) ($resuelta->Correcion ?? 0) === 1) ? 'Evaluado' : 'Entregado')
                            : 'Pendiente',
                        'adjuntos' => $adjuntos,
                    ];
                })
                ->values()
                ->all();

            $detalleClases[] = [
                'clase_id' => (int) $clase->ID,
                'orden' => (int) $clase->Orden,
                'titulo' => (string) $clase->Titulo,
                'guia_aprendizaje' => (string) $clase->Guia_Ap,
                'fecha' => !empty($clase->Fecha) ? Carbon::parse($clase->Fecha)->format('Y-m-d') : null,
                'fecha_publicacion' => (!empty($clase->Fecha_Publicacion) && $clase->Fecha_Publicacion !== '0000-00-00')
                    ? Carbon::parse($clase->Fecha_Publicacion)->format('Y-m-d')
                    : null,
                'leida' => true,
                'recursos' => $contenidos,
                'actividades' => $actividades,
            ];
        }

        return [
            'datos_generales' => [
                'materia' => $materiaLabel,
                'tipo_materia' => strtoupper($tipoMateria),
                'id_materia' => (int) $materiaId,
                'ciclo_lectivo' => $cicloId,
                'nombre_ciclo_lectivo' => (string) ($ciclo->Ciclo_lectivo ?? ''),
                'total_recursos' => (int) $totalRecursos,
                'total_clases' => (int) $totalClases,
                'total_tareas' => (int) $totalTareas,
                'total_foros' => (int) $totalForos,
            ],
            'detalle_recursos' => $recursosGenerales,
            'detalle_tareas' => $tareasGenerales,
            'detalle_clases' => $detalleClases,
        ];
    }

    private function countNovedades($studentId, $materiaId, $tipo, $cicloId, $ficl, $hoy)
    {
        $tareasSinLeer = DB::table('tareas_envios as te')
            ->join('tareas_virtuales as tv', 'te.ID_Tarea', '=', 'tv.ID')
            ->where('te.ID_Destinatario', $studentId)
            ->where('te.Envio', 1)
            ->where('te.Leido', 0)
            ->where('tv.ID_Materia', $materiaId)
            ->where('tv.Tipo_Materia', $tipo)
            ->where('tv.ID_Ciclo_Lectivo', $cicloId)
            ->where('tv.ID_Clase', 0)
            ->where('tv.Cerrada', 0)
            ->where('tv.Envio', 1)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('tv.Fecha_Publicacion')
                    ->orWhere('tv.Fecha_Publicacion', '<=', $hoy);
            })
            ->distinct('tv.ID')
            ->count('tv.ID');

        $recursosSinLeer = DB::table('clases_virtuales_contenidos as cvc')
            ->leftJoin('clases_virtuales_contenidos_lecturas as l', function ($join) use ($studentId) {
                $join->on('cvc.ID', '=', 'l.ID_Contenido')
                    ->where('l.ID_Alumno', '=', $studentId);
            })
            ->where('cvc.ID_Materia', $materiaId)
            ->where('cvc.Tipo_Materia', $tipo)
            ->where('cvc.Visible', 1)
            ->where('cvc.ID_Clase', 0)
            ->where('cvc.Estado', '<=', 1)
            ->where('cvc.Fecha', '>=', $ficl)
            ->where('cvc.Fecha', '<=', $hoy)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('cvc.Fecha_Vencimiento')
                    ->orWhere('cvc.Fecha_Vencimiento', '0000-00-00')
                    ->orWhere('cvc.Fecha_Vencimiento', '>=', $hoy);
            })
            ->whereNull('l.ID')
            ->count();

        return (int) $tareasSinLeer + (int) $recursosSinLeer;
    }

    public function getTareasGenerales($studentId, $materiaId, $tipoMateria, $cicloLectivo = null)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) {
            return null;
        }

        $tipoMateria = strtolower(trim((string) $tipoMateria));
        if (!in_array($tipoMateria, ['c', 'g'], true)) {
            return null;
        }

        $hoy = Carbon::now()->format('Y-m-d');

        $cicloQuery = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel);

        if (!empty($cicloLectivo)) {
            $ciclo = (clone $cicloQuery)
                ->where('Ciclo_lectivo', (int) $cicloLectivo)
                ->first();
        } else {
            $ciclo = null;
        }

        if (!$ciclo) {
            $ciclo = (clone $cicloQuery)
                ->where('Vigente', 'SI')
                ->orderByDesc('ID')
                ->first();
        }

        if (!$ciclo) {
            $ciclo = (clone $cicloQuery)->orderByDesc('ID')->first();
        }

        if (!$ciclo) {
            return null;
        }

        $cicloId = (int) $ciclo->ID;

        $tareas = DB::table('tareas_virtuales as tv')
            ->join('tareas_envios as te', function ($join) use ($studentId) {
                $join->on('te.ID_Tarea', '=', 'tv.ID')
                    ->where('te.ID_Destinatario', '=', $studentId)
                    ->where('te.Envio', '=', 1);
            })
            ->where('tv.ID_Materia', $materiaId)
            ->where('tv.Tipo_Materia', $tipoMateria)
            ->where('tv.ID_Clase', 0)
            ->where('tv.ID_Ciclo_Lectivo', $cicloId)
            ->where('tv.Cerrada', 0)
            ->where('tv.Envio', 1)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('tv.Fecha_Publicacion')
                    ->orWhere('tv.Fecha_Publicacion', '<=', $hoy);
            })
            ->orderBy('tv.Fecha', 'desc')
            ->select(
                'tv.*',
                'te.Leido as Leido_Envio',
                'te.Resuelto as Resuelto_Envio',
                'te.Corregido as Corregido_Envio'
            )
            ->get()
            ->map(function ($tarea) use ($studentId) {
                $resuelta = DB::table('tareas_resoluciones')
                    ->where('ID_Tarea', $tarea->ID)
                    ->where('ID_Alumno', $studentId)
                    ->first();

                $adjuntos = DB::table('tareas_adjuntos')
                    ->where('ID_Tarea', $tarea->ID)
                    ->orderBy('ID', 'asc')
                    ->get(['ID', 'Titulo', 'Archivo'])
                    ->map(function ($adj) {
                        return [
                            'id' => (int) $adj->ID,
                            'titulo' => (string) $adj->Titulo,
                            'archivo' => (string) $adj->Archivo,
                        ];
                    })
                    ->values()
                    ->all();

                $estadoPublicacion = ((int) $tarea->Cerrada === 0)
                    ? (((int) $tarea->Envio === 1) ? 'Publicada' : 'En producción')
                    : 'Oculta (Cerrada)';

                return [
                    'id_tarea' => (int) $tarea->ID,
                    'id_clase' => (int) $tarea->ID_Clase,
                    'tipo_id' => (int) $tarea->Tipo,
                    'tipo' => ((int) $tarea->Tipo === 1) ? 'Tarea' : 'Foro',
                    'titulo' => (string) $tarea->Titulo,
                    'consigna' => (string) $tarea->Consigna,
                    'estado' => $estadoPublicacion,
                    'fecha' => !empty($tarea->Fecha) ? Carbon::parse($tarea->Fecha)->format('Y-m-d') : null,
                    'fecha_publicacion' => (!empty($tarea->Fecha_Publicacion) && $tarea->Fecha_Publicacion != '0000-00-00')
                        ? Carbon::parse($tarea->Fecha_Publicacion)->format('Y-m-d')
                        : null,
                    'hora_publicacion' => (string) ($tarea->Hora_Publicacion ?? ''),
                    'fecha_vencimiento' => (!empty($tarea->Fecha_Vencimiento) && $tarea->Fecha_Vencimiento != '0000-00-00')
                        ? Carbon::parse($tarea->Fecha_Vencimiento)->format('Y-m-d')
                        : null,
                    'hora_vencimiento' => (string) ($tarea->Hora_Vencimiento ?? ''),
                    'leido' => (bool) ($tarea->Leido_Envio ?? 0),
                    'resuelto' => (bool) ($tarea->Resuelto_Envio ?? 0),
                    'corregido' => (bool) ($tarea->Corregido_Envio ?? 0),
                    'estado_resolucion' => $resuelta
                        ? (((int) ($resuelta->Correcion ?? 0) === 1) ? 'Evaluado' : 'Entregado')
                        : 'Pendiente',
                    'tiene_resolucion' => $resuelta ? true : false,
                    'adjuntos' => $adjuntos,
                ];
            })
            ->values()
            ->all();

        return $tareas;
    }

    public function getRecursosGenerales($studentId, $materiaId, $tipoMateria, $cicloLectivo = null)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) {
            return null;
        }

        $tipoMateria = strtolower(trim((string) $tipoMateria));
        if (!in_array($tipoMateria, ['c', 'g'], true)) {
            return null;
        }

        $hoy = Carbon::now()->format('Y-m-d');

        $cicloQuery = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel);

        if (!empty($cicloLectivo)) {
            $ciclo = (clone $cicloQuery)
                ->where('Ciclo_lectivo', (int) $cicloLectivo)
                ->first();
        } else {
            $ciclo = null;
        }

        if (!$ciclo) {
            $ciclo = (clone $cicloQuery)
                ->where('Vigente', 'SI')
                ->orderByDesc('ID')
                ->first();
        }

        if (!$ciclo) {
            $ciclo = (clone $cicloQuery)->orderByDesc('ID')->first();
        }

        if (!$ciclo) {
            return null;
        }

        $cicloId = (int) $ciclo->ID;
        $fechaInicioCiclo = !empty($ciclo->IPT) ? $ciclo->IPT : '2000-01-01';

        // 1. Obtenemos el ID de la agrupación (burbuja) del alumno
        $idAgrupacion = $this->resolveAgrupacionAlumno($alumno, $cicloId);

        // 2. Traemos todos los recursos base de la materia dentro del ciclo
        // Quitamos los wheres estrictos de Fecha_Vencimiento del query builder para manejarlos en el filter
        $recursosBase = DB::table('clases_virtuales_contenidos as cvc')
            ->join('clases_virtuales_contenidos_tipos as cvct', 'cvc.ID_Tipo', '=', 'cvct.ID')
            ->leftJoin('clases_virtuales_contenidos_lecturas as l', function ($join) use ($studentId) {
                $join->on('cvc.ID', '=', 'l.ID_Contenido')
                    ->where('l.ID_Alumno', '=', $studentId);
            })
            ->where('cvc.ID_Materia', $materiaId)
            ->where('cvc.Tipo_Materia', $tipoMateria)
            ->where('cvc.ID_Clase', 0)
            ->where('cvc.Visible', 1)
            ->where('cvc.Estado', '<=', 1)
            ->where('cvc.Fecha', '>=', $fechaInicioCiclo)
            ->orderBy('cvc.Fecha', 'desc')
            ->select(
                'cvc.*',
                'cvct.Tipo as NombreTipo',
                'cvct.Enlace as TipoEsEnlace',
                'l.ID as ID_Lectura'
            )
            ->get();

        // 3. Filtramos y mapeamos aplicando la lógica legacy exacta
        $recursos = $recursosBase->filter(function ($recurso) use ($idAgrupacion, $hoy) {
            // En el legacy PHP, curiosamente, "Fecha_Vencimiento" actúa como la FECHA DE PUBLICACIÓN del recurso.
            if ($idAgrupacion > 0) {
                $override = DB::table('tareas_virtuales_vencimientos')
                    ->where('ID_Tarea', $recurso->ID)
                    ->where('Tipo', '>', 2) // Tipo > 2 en el sistema legacy se usa para Contenidos/Recursos
                    ->where('ID_Agrupacion', $idAgrupacion)
                    ->first();

                $fechaPub = $override ? $override->Fecha_Vencimiento : $recurso->Fecha_Vencimiento;
            } else {
                $fechaPub = $recurso->Fecha_Vencimiento;
            }

            // Validar si ya es fecha de mostrarlo. Si está vacío o es '0000-00-00', asumimos que es visible siempre.
            if (empty($fechaPub) || $fechaPub === '0000-00-00') {
                return true;
            }

            return $hoy >= $fechaPub;
        })->map(function ($recurso) {
            $archivo = (string) ($recurso->Archivo ?? '');
            $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
            $leido = !empty($recurso->ID_Lectura);

            $tipoRecurso = 'archivo';
            if ((int) ($recurso->TipoEsEnlace ?? 0) === 1 || !empty($recurso->Enlace)) {
                $tipoRecurso = 'enlace';
            } elseif (in_array($extension, ['pdf'], true)) {
                $tipoRecurso = 'pdf';
            } elseif (in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'webm'], true)) {
                $tipoRecurso = 'video';
            }

            return [
                'id_recurso' => (int) $recurso->ID,
                'id_clase' => (int) $recurso->ID_Clase,
                'id_tipo_recurso' => (int) $recurso->ID_Tipo,
                'tipo_recurso' => (string) ($recurso->NombreTipo ?? $tipoRecurso),
                'tipo_recurso_codigo' => $tipoRecurso,
                'titulo' => (string) $recurso->Titulo,
                'descripcion' => (string) $recurso->Descripcion,
                'enlace' => (string) $recurso->Enlace,
                'archivo' => $archivo,
                'servidor' => (int) ($recurso->Servidor ?? 0),
                'fecha_publicacion' => !empty($recurso->Fecha) ? Carbon::parse($recurso->Fecha)->format('Y-m-d') : null,
                'fecha_vencimiento' => (!empty($recurso->Fecha_Vencimiento) && $recurso->Fecha_Vencimiento != '0000-00-00')
                    ? Carbon::parse($recurso->Fecha_Vencimiento)->format('Y-m-d')
                    : null,
                'url_codigo' => (string) ($recurso->Code ?? ''),
                'visible' => (int) ($recurso->Visible ?? 0),
                'estado' => (int) ($recurso->Estado ?? 0),
                'progreso' => $leido ? 100 : 0,
                'leido' => $leido,
            ];
        })
            ->values()
            ->all();

        return $recursos;
    }

    public function getClases($studentId, $materiaId, $tipoMateria, $cicloLectivo = null)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) {
            return null;
        }

        $tipoMateria = strtolower(trim((string) $tipoMateria));
        if (!in_array($tipoMateria, ['c', 'g'], true)) {
            return null;
        }

        $hoy = Carbon::now()->format('Y-m-d');

        $cicloQuery = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel);

        if (!empty($cicloLectivo)) {
            $ciclo = (clone $cicloQuery)
                ->where('Ciclo_lectivo', (int) $cicloLectivo)
                ->first();
        } else {
            $ciclo = null;
        }

        if (!$ciclo) {
            $ciclo = (clone $cicloQuery)
                ->where('Vigente', 'SI')
                ->orderByDesc('ID')
                ->first();
        }

        if (!$ciclo) {
            $ciclo = (clone $cicloQuery)
                ->orderByDesc('ID')
                ->first();
        }

        if (!$ciclo) {
            return null;
        }

        $cicloId = (int) $ciclo->ID;

        if ($tipoMateria === 'c') {
            $materiaRow = DB::table('materias as m')
                ->leftJoin('cursos as c', 'm.ID_Curso', '=', 'c.ID')
                ->leftJoin('personal as p', 'm.ID_Personal', '=', 'p.ID')
                ->where('m.ID', $materiaId)
                ->select(
                    'm.ID',
                    'm.Materia',
                    'm.ID_Curso',
                    'c.Cursos',
                    'p.Apellido',
                    'p.Nombre'
                )
                ->first();

            if (!$materiaRow) {
                return null;
            }

            $materiaLabel = trim((string) $materiaRow->Materia);
            if (!empty($materiaRow->Cursos)) {
                $materiaLabel .= ' (' . trim((string) $materiaRow->Cursos) . ')';
            }
        } else {
            $materiaRow = DB::table('materias_grupales as mg')
                ->leftJoin('personal as p', 'mg.ID_Personal', '=', 'p.ID')
                ->where('mg.ID', $materiaId)
                ->select(
                    'mg.ID',
                    'mg.Materia',
                    'mg.ID_Ciclo_Lectivo',
                    'p.Apellido',
                    'p.Nombre'
                )
                ->first();

            if (!$materiaRow) {
                return null;
            }

            $materiaLabel = trim((string) $materiaRow->Materia);
        }

        $clases = DB::table('clases_virtuales as cv')
            ->join('clases_virtuales_envios as cve', function ($join) use ($studentId) {
                $join->on('cve.ID_Clase', '=', 'cv.ID')
                    ->where('cve.ID_Destinatario', '=', $studentId)
                    ->where('cve.Envio', '=', 1);
            })
            ->where('cv.ID_Materia', $materiaId)
            ->where('cv.Tipo_Materia', $tipoMateria)
            ->where('cv.ID_Ciclo_Lectivo', $cicloId)
            ->where('cv.Estado', 1)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('cv.Fecha_Publicacion')
                    ->orWhere('cv.Fecha_Publicacion', '0000-00-00')
                    ->orWhere('cv.Fecha_Publicacion', '<=', $hoy);
            })
            ->orderBy('cv.Orden', 'desc')
            ->select(
                'cv.ID',
                'cv.Orden',
                'cv.Titulo',
                'cv.Guia_Ap',
                'cv.Fecha',
                'cv.Fecha_Publicacion',
                'cv.Estado',
                'cve.Leido as Leido_Clase'
            )
            ->get()
            ->map(function ($clase) use ($studentId) {
                $cantRecursos = DB::table('clases_virtuales_contenidos as cvc')
                    ->where('cvc.ID_Clase', $clase->ID)
                    ->where('cvc.Visible', 1)
                    ->where('cvc.Estado', '<=', 1)
                    ->count();

                $cantTareas = DB::table('clases_virtuales_actividades as cva')
                    ->join('tareas_virtuales as tv', 'cva.ID_Tarea', '=', 'tv.ID')
                    ->join('tareas_envios as te', function ($join) use ($studentId) {
                        $join->on('te.ID_Tarea', '=', 'tv.ID')
                            ->where('te.ID_Destinatario', '=', $studentId)
                            ->where('te.Envio', '=', 1);
                    })
                    ->where('cva.ID_Clase', $clase->ID)
                    ->where('cva.Visible', 1)
                    ->where('tv.Cerrada', 0)
                    ->where('tv.Envio', 1)
                    ->where('tv.Tipo', 1)
                    ->distinct('tv.ID')
                    ->count('tv.ID');

                $cantForos = DB::table('clases_virtuales_actividades as cva')
                    ->join('tareas_virtuales as tv', 'cva.ID_Tarea', '=', 'tv.ID')
                    ->join('tareas_envios as te', function ($join) use ($studentId) {
                        $join->on('te.ID_Tarea', '=', 'tv.ID')
                            ->where('te.ID_Destinatario', '=', $studentId)
                            ->where('te.Envio', '=', 1);
                    })
                    ->where('cva.ID_Clase', $clase->ID)
                    ->where('cva.Visible', 1)
                    ->where('tv.Cerrada', 0)
                    ->where('tv.Envio', 1)
                    ->where('tv.Tipo', 2)
                    ->distinct('tv.ID')
                    ->count('tv.ID');

                return [
                    'clase_id' => (int) $clase->ID,
                    'orden' => (int) $clase->Orden,
                    'titulo' => (string) $clase->Titulo,
                    'guia_aprendizaje' => (string) $clase->Guia_Ap,
                    'fecha' => !empty($clase->Fecha)
                        ? Carbon::parse($clase->Fecha)->format('Y-m-d')
                        : null,
                    'fecha_publicacion' => (!empty($clase->Fecha_Publicacion) && $clase->Fecha_Publicacion !== '0000-00-00')
                        ? Carbon::parse($clase->Fecha_Publicacion)->format('Y-m-d')
                        : null,
                    'leida' => (bool) ($clase->Leido_Clase ?? 0),
                    'cantidades' => [
                        'recursos' => (int) $cantRecursos,
                        'tareas' => (int) $cantTareas,
                        'foros' => (int) $cantForos,
                    ],
                ];
            })
            ->values()
            ->all();

        return [
            'datos_generales' => [
                'materia' => $materiaLabel,
                'tipo_materia' => strtoupper($tipoMateria),
                'id_materia' => (int) $materiaId,
                'ciclo_lectivo' => $cicloId,
                'nombre_ciclo_lectivo' => (string) ($ciclo->Ciclo_lectivo ?? ''),
            ],
            'clases' => $clases,
        ];
    }

    public function getDetalleTarea($studentId, $materiaId, $tipoMateria, $taskId, $cicloLectivo = null)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) {
            return null;
        }

        $tipoMateria = strtolower(trim((string) $tipoMateria));
        if (!in_array($tipoMateria, ['c', 'g'], true)) {
            throw new \RuntimeException('Tipo de materia inválido.');
        }

        $ciclo = $this->resolveCicloAlumno($alumno, $cicloLectivo);
        if (!$ciclo) {
            return null;
        }

        $tarea = $this->findTareaAlumno($studentId, $materiaId, $tipoMateria, $taskId, (int) $ciclo->ID);
        if (!$tarea) {
            return null;
        }

        $now = Carbon::now('America/Argentina/Buenos_Aires');

        TaskSubmission::where('ID_Tarea', $taskId)
            ->where('ID_Destinatario', $studentId)
            ->where('Leido', 0)
            ->update([
                'Leido' => 1,
                'Fecha_Leido' => $now->format('Y-m-d'),
                'Hora_Leido' => $now->format('H:i:s'),
            ]);

        $resolucion = TareaResolucion::where('ID_Tarea', $taskId)
            ->where('ID_Alumno', $studentId)
            ->first();

        $adjuntosDocente = TareaAdjunto::where('ID_Tarea', $taskId)
            ->orderBy('ID', 'asc')
            ->get(['ID', 'Titulo', 'Archivo'])
            ->map(function ($adj) {
                return [
                    'id' => (int) $adj->ID,
                    'titulo' => (string) $adj->Titulo,
                    'archivo' => (string) $adj->Archivo,
                ];
            })
            ->values()
            ->all();

        $adjuntosAlumno = TareaResolucionAdjunto::where('ID_Tarea', $taskId)
            ->where('ID_Alumno', $studentId)
            ->orderBy('ID', 'asc')
            ->get(['ID', 'Archivo', 'Fecha', 'Hora', 'Leido', 'Servidor'])
            ->map(function ($adj) {
                return [
                    'id' => (int) $adj->ID,
                    'archivo' => (string) $adj->Archivo,
                    'fecha' => (string) $adj->Fecha,
                    'hora' => (string) $adj->Hora,
                    'leido' => (int) $adj->Leido,
                    'servidor' => (int) $adj->Servidor,
                ];
            })
            ->values()
            ->all();

        $adjuntosCorreccion = TareaDevolucionAdjunto::where('ID_Tarea', $taskId)
            ->where('ID_Alumno', $studentId)
            ->orderBy('ID', 'asc')
            ->get(['ID', 'Archivo'])
            ->map(function ($adj) {
                return [
                    'id' => (int) $adj->ID,
                    'archivo' => (string) $adj->Archivo,
                ];
            })
            ->values()
            ->all();

        $vencimiento = $this->resolveVencimientoTarea($alumno, $tarea, (int) $ciclo->ID);
        $fechaVencimiento = $vencimiento['fecha'];
        $horaVencimiento = $vencimiento['hora'];
        $vencida = $this->isTareaVencida($fechaVencimiento, $horaVencimiento);

        $estadoPublicacion = ((int) $tarea->Cerrada === 0)
            ? (((int) $tarea->Envio === 1) ? 'Publicada' : 'En producción')
            : 'Oculta (Cerrada)';

        return [
            'id_tarea' => (int) $tarea->ID,
            'id_materia' => (int) $tarea->ID_Materia,
            'tipo_materia' => strtoupper((string) $tarea->Tipo_Materia),
            'id_clase' => (int) $tarea->ID_Clase,
            'tipo_id' => (int) $tarea->Tipo,
            'tipo_tarea' => 'Tarea',
            'titulo' => (string) $tarea->Titulo,
            'consigna' => (string) $tarea->Consigna,
            'estado' => $estadoPublicacion,
            'fecha' => !empty($tarea->Fecha) ? Carbon::parse($tarea->Fecha)->format('Y-m-d') : null,
            'fecha_publicacion' => (!empty($tarea->Fecha_Publicacion) && $tarea->Fecha_Publicacion !== '0000-00-00')
                ? Carbon::parse($tarea->Fecha_Publicacion)->format('Y-m-d')
                : null,
            'hora_publicacion' => (string) ($tarea->Hora_Publicacion ?? ''),
            'fecha_vencimiento' => $fechaVencimiento,
            'hora_vencimiento' => $horaVencimiento,
            'cerrada' => (int) $tarea->Cerrada,
            'envio' => (int) $tarea->Envio,
            'dest_sel' => (int) ($tarea->Dest_Sel ?? 0),
            'leido' => (bool) ($tarea->Leido_Envio ?? 0),
            'resuelto' => (bool) ($tarea->Resuelto_Envio ?? 0),
            'corregido' => (bool) ($tarea->Corregido_Envio ?? 0),
            'vencida' => $vencida ? 1 : 0,
            'puede_entregar' => (((int) $tarea->Cerrada === 0) && !$vencida) ? 1 : 0,
            'adjuntos_docente' => $adjuntosDocente,
            'resolucion_alumno' => $resolucion ? [
                'id' => (int) $resolucion->ID,
                'resolucion' => (string) ($resolucion->Resolucion ?? ''),
                'fecha' => (string) ($resolucion->Fecha ?? ''),
                'hora' => (string) ($resolucion->Hora ?? ''),
                'leido' => (int) ($resolucion->Leido ?? 0),
                'correcion' => (int) ($resolucion->Correcion ?? 0),
                'comentario_correccion' => (string) ($resolucion->Comentario_Correccion ?? ''),
                'fecha_correccion' => (string) ($resolucion->Fecha_Correccion ?? ''),
                'hora_correccion' => (string) ($resolucion->Hora_Correccion ?? ''),
            ] : null,
            'adjuntos_alumno' => $adjuntosAlumno,
            'adjuntos_correccion' => $adjuntosCorreccion,
            'estado_resolucion' => $resolucion
                ? (((int) ($resolucion->Correcion ?? 0) === 1) ? 'Evaluado' : 'Entregado')
                : 'Pendiente',
        ];
    }

    public function guardarResolucionTarea($studentId, $materiaId, $tipoMateria, $taskId, $institucionId, array $payload, array $archivos = [])
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) {
            throw new \RuntimeException('El alumno indicado no existe.');
        }

        $tipoMateria = strtolower(trim((string) $tipoMateria));
        if (!in_array($tipoMateria, ['c', 'g'], true)) {
            throw new \RuntimeException('Tipo de materia inválido.');
        }

        $ciclo = $this->resolveCicloAlumno($alumno, null);
        if (!$ciclo) {
            throw new \RuntimeException('No se encontró ciclo lectivo para el alumno.');
        }

        $tarea = $this->findTareaAlumno($studentId, $materiaId, $tipoMateria, $taskId, (int) $ciclo->ID);
        if (!$tarea) {
            throw new \RuntimeException('La tarea no existe o no pertenece al alumno.');
        }

        if ((int) $tarea->Tipo !== 1) {
            throw new \RuntimeException('La actividad indicada no es una tarea.');
        }

        if ((int) $tarea->Cerrada === 1) {
            throw new \RuntimeException('La tarea está cerrada y no admite entregas.');
        }

        $vencimiento = $this->resolveVencimientoTarea($alumno, $tarea, (int) $ciclo->ID);
        if ($this->isTareaVencida($vencimiento['fecha'], $vencimiento['hora'])) {
            throw new \RuntimeException('La tarea está vencida y no admite entregas.');
        }

        $textoResolucion = trim((string) ($payload['resolucion'] ?? ''));
        $now = Carbon::now('America/Argentina/Buenos_Aires');

        DB::beginTransaction();

        try {
            $res = TareaResolucion::where('ID_Tarea', $taskId)
                ->where('ID_Alumno', $studentId)
                ->first();

            if (!$res) {
                $res = new TareaResolucion();
                $res->ID_Tarea = (int) $taskId;
                $res->ID_Alumno = (int) $studentId;
            }

            $res->Resolucion = $textoResolucion;
            $res->Fecha = $now->format('Y-m-d');
            $res->Hora = $now->format('H:i:s');
            $res->Leido = 0;
            $res->Fecha_Leido = '0000-00-00';
            $res->Hora_Leido = '00:00:00';
            $res->Correcion = 0;
            $res->Comentario_Correccion = '';
            $res->Fecha_Correccion = '0000-00-00';
            $res->Hora_Correccion = '00:00:00';
            $res->save();

            $updatedEnvios = TareaEnvio::where('ID_Tarea', $taskId)
                ->where('ID_Destinatario', $studentId)
                ->update([
                    'Resuelto' => 1,
                    'Leido' => 1,
                    'Fecha_Leido' => $now->format('Y-m-d'),
                    'Hora_Leido' => $now->format('H:i:s'),
                ]);

            if ((int) $updatedEnvios === 0) {
                TareaEnvio::create([
                    'ID_Tarea' => (int) $taskId,
                    'ID_Destinatario' => (int) $studentId,
                    'Aleatorio' => '',
                    'Envio' => 1,
                    'Leido' => 1,
                    'Fecha_Leido' => $now->format('Y-m-d'),
                    'Hora_Leido' => $now->format('H:i:s'),
                    'IP_Leido' => '',
                    'MailD' => '',
                    'Resuelto' => 1,
                    'Corregido' => 0,
                ]);
            }

            $adjuntosInsertados = 0;
            $nombresAdjuntos = [];

            if (!empty($archivos)) {
                $institucion = DatabaseManager::getInstitutionData((int) $institucionId);
                $carpeta = $institucion->Carpeta ?? null;

                if (!$carpeta) {
                    throw new \RuntimeException('No se pudo determinar la carpeta institucional para guardar adjuntos.');
                }

                $ruta = (string) config('app.ruta_tareas');
                $ruta = str_replace('{carpeta}', $carpeta, $ruta);
                $ruta = rtrim($ruta, "\\/");

                if (!file_exists($ruta)) {
                    @mkdir($ruta, 0755, true);
                }

                if (!file_exists($ruta)) {
                    throw new \RuntimeException('No se pudo crear la carpeta destino para adjuntos.');
                }

                foreach ($archivos as $file) {
                    if (!$file || !method_exists($file, 'isValid') || !$file->isValid()) {
                        continue;
                    }

                    $nombreOriginal = (string) $file->getClientOriginalName();
                    $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

                    if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'pdf', 'ppt', 'pptx', 'mp3', 'mp4', 'mov', 'zip'], true)) {
                        throw new \RuntimeException('Formato de archivo no permitido: ' . $ext);
                    }

                    $nombreFinal = $this->buildSafeFileName($nombreOriginal);
                    $file->move($ruta, $nombreFinal);

                    $adj = new TareaResolucionAdjunto();
                    $adj->ID_Tarea = (int) $taskId;
                    $adj->ID_Alumno = (int) $studentId;
                    $adj->Archivo = $nombreFinal;
                    $adj->Fecha = $now->format('Y-m-d');
                    $adj->Hora = $now->format('H:i:s');
                    $adj->Leido = 0;
                    $adj->Servidor = 0;
                    $adj->save();

                    $adjuntosInsertados++;
                    $nombresAdjuntos[] = $nombreFinal;
                }
            }

            DB::commit();

            return [
                'id_tarea' => (int) $taskId,
                'id_alumno' => (int) $studentId,
                'id_resolucion' => (int) $res->ID,
                'resuelto' => 1,
                'corregido' => 0,
                'estado_resolucion' => 'Entregado',
                'fecha' => $now->format('Y-m-d'),
                'hora' => $now->format('H:i:s'),
                'adjuntos_insertados' => (int) $adjuntosInsertados,
                'adjuntos' => $nombresAdjuntos,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error guardarResolucionTarea', [
                'student_id' => (int) $studentId,
                'materia_id' => (int) $materiaId,
                'tipo_materia' => (string) $tipoMateria,
                'task_id' => (int) $taskId,
                'msg' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function listarForosClaseAlumno($studentId, $materiaId, $tipoMateria, $classId, $cicloLectivo = null)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) {
            return null;
        }

        $tipoMateria = strtolower(trim((string) $tipoMateria));
        if (!in_array($tipoMateria, ['c', 'g'], true)) {
            throw new \RuntimeException('Tipo de materia inválido.');
        }

        $ciclo = $this->resolveCicloAlumno($alumno, $cicloLectivo);
        if (!$ciclo) {
            return null;
        }

        $hoy = Carbon::now('America/Argentina/Buenos_Aires')->format('Y-m-d');

        $items = DB::table('tareas_virtuales as tv')
            ->join('tareas_envios as te', function ($join) use ($studentId) {
                $join->on('te.ID_Tarea', '=', 'tv.ID')
                    ->where('te.ID_Destinatario', '=', $studentId)
                    ->where('te.Envio', '=', 1);
            })
            ->where('tv.ID_Materia', $materiaId)
            ->where('tv.Tipo_Materia', $tipoMateria)
            ->where('tv.ID_Clase', $classId)
            ->where('tv.ID_Ciclo_Lectivo', (int) $ciclo->ID)
            ->where('tv.Tipo', 2)
            ->where('tv.Envio', 1)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('tv.Fecha_Publicacion')
                    ->orWhere('tv.Fecha_Publicacion', '0000-00-00')
                    ->orWhere('tv.Fecha_Publicacion', '<=', $hoy);
            })
            ->orderBy('tv.ID', 'desc')
            ->select(
                'tv.ID',
                'tv.Titulo',
                'tv.Fecha',
                'tv.Envio',
                'tv.Cerrada',
                'tv.ID_Clase',
                'te.Leido as Leido_Envio'
            )
            ->distinct()
            ->get();

        $out = [];
        foreach ($items as $t) {
            $out[] = [
                'id_foro' => (int) $t->ID,
                'id_clase' => (int) $t->ID_Clase,
                'titulo' => (string) $t->Titulo,
                'fecha' => !empty($t->Fecha) ? Carbon::parse($t->Fecha)->format('d/m/Y') : '',
                'envio' => (int) $t->Envio,
                'cerrada' => (int) $t->Cerrada,
                'leido' => (int) ($t->Leido_Envio ?? 0),
            ];
        }

        return [
            'id_materia' => (int) $materiaId,
            'tipo_materia' => strtoupper((string) $tipoMateria),
            'id_clase' => (int) $classId,
            'foros' => $out,
        ];
    }

    public function detalleForoAlumno($studentId, $forumId, $institucionId)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) {
            return null;
        }

        $foro = $this->findForoAlumno($studentId, $forumId);
        if (!$foro) {
            return null;
        }

        DB::table('tareas_envios')
            ->where('ID_Tarea', (int) $forumId)
            ->where('ID_Destinatario', (int) $studentId)
            ->where('Envio', 1)
            ->update([
                'Leido' => 1,
                'Fecha_Leido' => Carbon::now('America/Argentina/Buenos_Aires')->format('Y-m-d'),
                'Hora_Leido' => Carbon::now('America/Argentina/Buenos_Aires')->format('H:i:s'),
            ]);

        $filesMeta = $this->resolveInstitutionFilesMeta((int) $institucionId);

        $adjuntos = DB::table('tareas_adjuntos')
            ->where('ID_Tarea', (int) $foro->ID)
            ->orderBy('ID', 'asc')
            ->get(['ID', 'Archivo']);

        $adjuntosList = [];
        foreach ($adjuntos as $a) {
            $adjuntosList[] = [
                'id_adjunto' => (int) $a->ID,
                'adjunto' => (string) $a->Archivo,
                'url_adjunto' => $filesMeta['base_tasks_url'] . (string) $a->Archivo,
            ];
        }

        $base = DB::table('tareas_envios as te')
            ->where('te.ID_Tarea', (int) $foro->ID)
            ->selectRaw("
            COUNT(DISTINCT te.ID_Destinatario) as total_asignados,
            COUNT(DISTINCT CASE WHEN te.Leido = 1 THEN te.ID_Destinatario END) as leidos
        ")
            ->first();

        $foroStats = DB::table('tareas_virtuales_foros as tf')
            ->where('tf.ID_Tarea', (int) $foro->ID)
            ->where('tf.B', 0)
            ->selectRaw("
            COUNT(*) as mensajes_activos,
            SUM(CASE WHEN tf.ID_Respuesta = 0 THEN 1 ELSE 0 END) as threads,
            SUM(CASE WHEN tf.ID_Respuesta > 0 THEN 1 ELSE 0 END) as respuestas,
            COUNT(DISTINCT CASE WHEN tf.Tipo_Usuario = 2 THEN tf.ID_Usuario END) as participaron
        ")
            ->first();

        $adjInterv = DB::table('tareas_foros_adjuntos as tfa')
            ->join('tareas_virtuales_foros as tf', 'tf.ID', '=', 'tfa.ID_Intervencion')
            ->where('tf.ID_Tarea', (int) $foro->ID)
            ->where('tf.B', 0)
            ->selectRaw('COUNT(*) as adjuntos')
            ->first();

        $estado = ((int) $foro->Cerrada === 0)
            ? (((int) $foro->Envio === 1) ? 'Publicada' : 'En producción')
            : 'Oculta (Cerrada)';

        return [
            'id_foro' => (int) $foro->ID,
            'id_materia' => (int) $foro->ID_Materia,
            'tipo_materia' => strtoupper((string) $foro->Tipo_Materia),
            'id_clase' => (int) $foro->ID_Clase,
            'foro' => [
                'titulo' => (string) $foro->Titulo,
                'consigna' => (string) $foro->Consigna,
                'estado' => $estado,
                'fecha' => !empty($foro->Fecha) ? Carbon::parse($foro->Fecha)->format('d/m/Y') : '',
                'fecha_publicacion' => (!empty($foro->Fecha_Publicacion) && $foro->Fecha_Publicacion !== '0000-00-00')
                    ? Carbon::parse($foro->Fecha_Publicacion)->format('d/m/Y')
                    : '',
                'hora_publicacion' => (string) ($foro->Hora_Publicacion ?? ''),
                'fecha_vencimiento' => (!empty($foro->Fecha_Vencimiento) && $foro->Fecha_Vencimiento !== '0000-00-00')
                    ? Carbon::parse($foro->Fecha_Vencimiento)->format('d/m/Y')
                    : '',
                'hora_vencimiento' => (string) ($foro->Hora_Vencimiento ?? ''),
                'dest_sel' => (int) ($foro->Dest_Sel ?? 0),
                'cerrada' => (int) $foro->Cerrada,
                'envio' => (int) $foro->Envio,
                'leido' => (int) ($foro->Leido_Envio ?? 0),
            ],
            'adjuntos_tarea' => $adjuntosList,
            'estadisticas' => [
                'total_asignados' => (int) ($base->total_asignados ?? 0),
                'leidos' => (int) ($base->leidos ?? 0),
                'participaron' => (int) ($foroStats->participaron ?? 0),
                'mensajes_activos' => (int) ($foroStats->mensajes_activos ?? 0),
                'threads' => (int) ($foroStats->threads ?? 0),
                'respuestas' => (int) ($foroStats->respuestas ?? 0),
                'adjuntos' => (int) ($adjInterv->adjuntos ?? 0),
            ],
        ];
    }

    public function listarForoAlumnoPaginado($studentId, $forumId, $institucionId, $perPage = 50, $idRespuesta = 0, $order = 'DESC')
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) {
            throw new \RuntimeException('El alumno indicado no existe.');
        }

        $foro = $this->findForoAlumno($studentId, $forumId);
        if (!$foro) {
            throw new \RuntimeException('El foro indicado no existe o no pertenece al alumno.');
        }

        $idRespuesta = (int) $idRespuesta;
        if ($idRespuesta > 0) {
            $padreOk = DB::table('tareas_virtuales_foros')
                ->where('ID', $idRespuesta)
                ->where('ID_Tarea', $forumId)
                ->where('B', 0)
                ->exists();

            if (!$padreOk) {
                throw new \RuntimeException('La intervención padre no existe.');
            }
        }

        $order = strtoupper(trim((string) $order));
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        $perPage = (int) $perPage;
        if ($perPage <= 0) {
            $perPage = 50;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $filesMeta = $this->resolveInstitutionFilesMeta((int) $institucionId);

        $p = DB::table('tareas_virtuales_foros')
            ->where('ID_Tarea', $forumId)
            ->where('B', 0)
            ->where('ID_Respuesta', $idRespuesta)
            ->orderBy('ID', $order)
            ->paginate($perPage);

        $items = $p->items();

        $idsPagina = [];
        foreach ($items as $it) {
            $id = is_object($it) ? (int) ($it->ID ?? 0) : 0;
            if ($id > 0) {
                $idsPagina[] = $id;
            }
        }

        $marcados = 0;
        if (!empty($idsPagina)) {
            $now = Carbon::now('America/Argentina/Buenos_Aires');

            $marcados = (int) DB::table('tareas_virtuales_foros')
                ->whereIn('ID', $idsPagina)
                ->where('ID_Tarea', $forumId)
                ->where('B', 0)
                ->where('Tipo_Usuario', '<>', 2)
                ->where('Leido', 0)
                ->update([
                    'Leido' => 1,
                    'Fecha_Leido' => $now->format('Y-m-d'),
                    'Hora_Leido' => $now->format('H:i:s'),
                ]);
        }

        $adjuntosPorIntervencion = [];
        if (!empty($idsPagina)) {
            $rowsAdj = DB::table('tareas_foros_adjuntos')
                ->whereIn('ID_Intervencion', $idsPagina)
                ->orderBy('ID', 'asc')
                ->get(['ID', 'ID_Intervencion', 'Archivo']);

            foreach ($rowsAdj as $a) {
                $idInterv = (int) $a->ID_Intervencion;
                if (!isset($adjuntosPorIntervencion[$idInterv])) {
                    $adjuntosPorIntervencion[$idInterv] = [];
                }

                $adjuntosPorIntervencion[$idInterv][] = [
                    'id_adjunto' => (int) $a->ID,
                    'adjunto' => (string) $a->Archivo,
                    'url_adjunto' => $filesMeta['base_tasks_url'] . (string) $a->Archivo,
                ];
            }
        }

        $idsAlumnos = [];
        $idsPersonal = [];

        foreach ($items as $it) {
            $tipoUsu = (int) ($it->Tipo_Usuario ?? 0);
            $idUsu = (int) ($it->ID_Usuario ?? 0);

            if ($idUsu <= 0) {
                continue;
            }

            if ($tipoUsu === 2) {
                $idsAlumnos[] = $idUsu;
            } else {
                $idsPersonal[] = $idUsu;
            }
        }

        $alumnosById = [];
        $avatarByAlumnoId = [];

        if (!empty($idsAlumnos)) {
            $rows = DB::table('alumnos')
                ->whereIn('ID', array_values(array_unique($idsAlumnos)))
                ->get(['ID', 'Nombre', 'Apellido', 'Perfil']);

            foreach ($rows as $r) {
                $idA = (int) $r->ID;
                $alumnosById[$idA] = trim((string) $r->Apellido) . ', ' . trim((string) $r->Nombre);

                $perfil = trim((string) ($r->Perfil ?? ''));
                $avatarByAlumnoId[$idA] = $perfil !== '' ? ($filesMeta['base_avatar_url'] . $perfil) : '';
            }
        }

        $personalById = [];
        if (!empty($idsPersonal)) {
            $rows = DB::table('personal')
                ->whereIn('ID', array_values(array_unique($idsPersonal)))
                ->get(['ID', 'Nombre', 'Apellido']);

            foreach ($rows as $r) {
                $personalById[(int) $r->ID] = trim((string) $r->Apellido) . ', ' . trim((string) $r->Nombre);
            }
        }

        $replyCountByRoot = [];
        $replyUnreadByRoot = [];

        if (!empty($idsPagina)) {
            $rows = DB::table('tareas_virtuales_foros')
                ->where('ID_Tarea', $forumId)
                ->where('B', 0)
                ->whereIn('ID_Respuesta', $idsPagina)
                ->groupBy('ID_Respuesta')
                ->selectRaw('ID_Respuesta as Root, COUNT(*) as Cant')
                ->get();

            foreach ($rows as $r) {
                $replyCountByRoot[(int) $r->Root] = (int) $r->Cant;
            }

            $rowsU = DB::table('tareas_virtuales_foros')
                ->where('ID_Tarea', $forumId)
                ->where('B', 0)
                ->whereIn('ID_Respuesta', $idsPagina)
                ->where('Tipo_Usuario', '<>', 2)
                ->where('Leido', 0)
                ->groupBy('ID_Respuesta')
                ->selectRaw('ID_Respuesta as Root, COUNT(*) as Cant')
                ->get();

            foreach ($rowsU as $r) {
                $replyUnreadByRoot[(int) $r->Root] = (int) $r->Cant;
            }
        }

        $itemsOut = [];
        foreach ($items as $it) {
            $arr = (array) $it;

            $idInterv = (int) ($arr['ID'] ?? 0);
            $tipoUsu = (int) ($arr['Tipo_Usuario'] ?? 0);
            $idUsu = (int) ($arr['ID_Usuario'] ?? 0);

            $nombre = '';
            $avatar = '';

            if ($tipoUsu === 2) {
                $nombre = isset($alumnosById[$idUsu]) ? $alumnosById[$idUsu] : '';
                $avatar = isset($avatarByAlumnoId[$idUsu]) ? $avatarByAlumnoId[$idUsu] : '';
            } else {
                $nombre = isset($personalById[$idUsu]) ? $personalById[$idUsu] : '';
            }

            $itemsOut[] = [
                'id' => (int) ($arr['ID'] ?? 0),
                'id_tarea' => (int) ($arr['ID_Tarea'] ?? 0),
                'id_usuario' => $idUsu,
                'tipo_usuario' => $tipoUsu,
                'fecha' => (string) ($arr['Fecha'] ?? ''),
                'hora' => (string) ($arr['Hora'] ?? ''),
                'mensaje' => (string) ($arr['Mensaje'] ?? ''),
                'leido' => (int) ($arr['Leido'] ?? 0),
                'id_respuesta' => (int) ($arr['ID_Respuesta'] ?? 0),
                'usuario_nombre' => $nombre,
                'usuario_avatar' => $avatar,
                'adjuntos' => isset($adjuntosPorIntervencion[$idInterv]) ? $adjuntosPorIntervencion[$idInterv] : [],
                'respuestas' => [
                    'count' => (int) (isset($replyCountByRoot[$idInterv]) ? $replyCountByRoot[$idInterv] : 0),
                    'unread' => (int) (isset($replyUnreadByRoot[$idInterv]) ? $replyUnreadByRoot[$idInterv] : 0),
                    'id_padre' => $idInterv,
                ],
            ];
        }

        return [
            'items' => $itemsOut,
            'pagination' => [
                'per_page' => (int) $p->perPage(),
                'current_page' => (int) $p->currentPage(),
                'total_pages' => (int) $p->lastPage(),
                'total_items' => (int) $p->total(),
                'count' => count($itemsOut),
                'has_more' => $p->hasMorePages(),
                'order' => $order,
            ],
            'filters' => [
                'id_foro' => (int) $forumId,
                'id_respuesta' => (int) $idRespuesta,
            ],
            'read' => [
                'marcados' => (int) $marcados,
            ],
        ];
    }

    public function enviarIntervencionForoAlumno($studentId, $forumId, $institucionId, array $payload, array $archivos = [])
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) {
            throw new \RuntimeException('El alumno indicado no existe.');
        }

        $foro = $this->findForoAlumno($studentId, $forumId);
        if (!$foro) {
            throw new \RuntimeException('El foro indicado no existe o no pertenece al alumno.');
        }

        if ((int) $foro->Cerrada === 1) {
            throw new \RuntimeException('El foro está cerrado y no admite nuevas intervenciones.');
        }

        $mensaje = trim((string) ($payload['mensaje'] ?? ''));
        if ($mensaje === '') {
            throw new \RuntimeException('El mensaje es obligatorio.');
        }

        $idRespuesta = (int) ($payload['id_respuesta'] ?? 0);
        if ($idRespuesta > 0) {
            $existePadre = DB::table('tareas_virtuales_foros')
                ->where('ID', $idRespuesta)
                ->where('ID_Tarea', $forumId)
                ->where('B', 0)
                ->exists();

            if (!$existePadre) {
                throw new \RuntimeException('La intervención a responder no existe en este foro.');
            }
        }

        $archivosMovidos = [];
        $now = Carbon::now('America/Argentina/Buenos_Aires');

        DB::beginTransaction();

        try {
            $idIntervencion = DB::table('tareas_virtuales_foros')->insertGetId([
                'ID_Tarea' => (int) $forumId,
                'ID_Usuario' => (int) $studentId,
                'Tipo_Usuario' => 2,
                'Fecha' => $now->format('Y-m-d'),
                'Hora' => $now->format('H:i:s'),
                'Mensaje' => $mensaje,
                'B' => 0,
                'Leido' => 0,
                'Fecha_Leido' => '0000-00-00',
                'Hora_Leido' => '00:00:00',
                'ID_Respuesta' => $idRespuesta > 0 ? $idRespuesta : 0,
            ]);

            $insertadosAdj = 0;
            $nombresAdj = [];

            if (!empty($archivos) && is_array($archivos)) {
                $filesMeta = $this->resolveInstitutionFilesMeta((int) $institucionId);
                $ruta = $filesMeta['filesystem_path'];

                $extPermitidas = ['png', 'jpg', 'jpeg', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'pdf', 'ppt', 'pptx', 'mp3', 'mp4', 'mov', 'zip'];

                foreach ($archivos as $file) {
                    if (!$file || !method_exists($file, 'isValid') || !$file->isValid()) {
                        continue;
                    }

                    $nombreOriginal = (string) $file->getClientOriginalName();
                    $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

                    if ($ext !== '' && !in_array($ext, $extPermitidas, true)) {
                        throw new \RuntimeException('Formato de archivo no permitido: ' . $ext);
                    }

                    $nombreFinal = $this->buildSafeFileName($nombreOriginal);
                    $file->move($ruta, $nombreFinal);

                    $archivosMovidos[] = $ruta . DIRECTORY_SEPARATOR . $nombreFinal;

                    DB::table('tareas_foros_adjuntos')->insert([
                        'ID_Intervencion' => (int) $idIntervencion,
                        'Archivo' => $nombreFinal,
                        'Servidor' => 1,
                    ]);

                    $insertadosAdj++;
                    $nombresAdj[] = $nombreFinal;
                }
            }

            DB::commit();

            return [
                'id_foro' => (int) $forumId,
                'id_intervencion' => (int) $idIntervencion,
                'id_respuesta' => $idRespuesta > 0 ? $idRespuesta : 0,
                'adjuntos' => [
                    'insertados' => $insertadosAdj,
                    'archivos' => $nombresAdj,
                ],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            foreach ($archivosMovidos as $path) {
                try {
                    if (is_string($path) && $path !== '' && file_exists($path)) {
                        @unlink($path);
                    }
                } catch (\Throwable $ignored) {
                }
            }

            Log::error('Error enviarIntervencionForoAlumno', [
                'student_id' => (int) $studentId,
                'forum_id' => (int) $forumId,
                'id_respuesta' => $idRespuesta,
                'msg' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function detalleClaseAlumno($studentId, $materiaId, $tipoMateria, $classId, $institucionId, $cicloLectivo = null)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) {
            return null;
        }

        $tipoMateria = strtolower(trim((string) $tipoMateria));
        if (!in_array($tipoMateria, ['c', 'g'], true)) {
            throw new \RuntimeException('Tipo de materia inválido.');
        }

        $ciclo = $this->resolveCicloAlumno($alumno, $cicloLectivo);
        if (!$ciclo) {
            return null;
        }

        $clase = $this->findClaseAlumno($studentId, $materiaId, $tipoMateria, $classId, (int) $ciclo->ID);
        if (!$clase) {
            return null;
        }

        $now = Carbon::now('America/Argentina/Buenos_Aires');

        DB::table('clases_virtuales_envios')
            ->where('ID_Clase', (int) $classId)
            ->where('ID_Destinatario', (int) $studentId)
            ->where('Envio', 1)
            ->update([
                'Leido' => 1,
                'Fecha_Leido' => $now->format('Y-m-d'),
                'Hora_Leido' => $now->format('H:i:s'),
            ]);

        $materiaLabel = $this->resolveMateriaLabelAlumno((int) $materiaId, $tipoMateria);

        $cantRecursosClase = (int) DB::table('clases_virtuales_contenidos as cvc')
            ->where('cvc.ID_Materia', (int) $materiaId)
            ->where('cvc.Tipo_Materia', $tipoMateria)
            ->where('cvc.ID_Clase', (int) $classId)
            ->where('cvc.Visible', 1)
            ->where('cvc.Estado', '<=', 1)
            ->count();

        $cantTareasClase = (int) DB::table('clases_virtuales_actividades as cva')
            ->join('tareas_virtuales as tv', 'cva.ID_Tarea', '=', 'tv.ID')
            ->join('tareas_envios as te', function ($join) use ($studentId) {
                $join->on('te.ID_Tarea', '=', 'tv.ID')
                    ->where('te.ID_Destinatario', '=', $studentId)
                    ->where('te.Envio', '=', 1);
            })
            ->where('cva.ID_Clase', (int) $classId)
            ->where('cva.Visible', 1)
            ->where('tv.Tipo', 1)
            ->where('tv.Cerrada', 0)
            ->where('tv.Envio', 1)
            ->distinct()
            ->count('tv.ID');

        $cantForosClase = (int) DB::table('clases_virtuales_actividades as cva')
            ->join('tareas_virtuales as tv', 'cva.ID_Tarea', '=', 'tv.ID')
            ->join('tareas_envios as te', function ($join) use ($studentId) {
                $join->on('te.ID_Tarea', '=', 'tv.ID')
                    ->where('te.ID_Destinatario', '=', $studentId)
                    ->where('te.Envio', '=', 1);
            })
            ->where('cva.ID_Clase', (int) $classId)
            ->where('cva.Visible', 1)
            ->where('tv.Tipo', 2)
            ->where('tv.Cerrada', 0)
            ->where('tv.Envio', 1)
            ->distinct()
            ->count('tv.ID');

        $lectura = DB::table('clases_virtuales_envios')
            ->where('ID_Clase', (int) $classId)
            ->where('ID_Destinatario', (int) $studentId)
            ->where('Envio', 1)
            ->orderBy('ID', 'desc')
            ->first([
                'Leido',
                'Fecha_Leido',
                'Hora_Leido',
            ]);

        $visibleClaseLabel = ((int) $clase->Estado === 1) ? 'Visible' : 'Oculta';

        return [
            'datos_generales' => [
                'id_materia' => (int) $materiaId,
                'tipo_materia' => strtoupper((string) $tipoMateria),
                'materia' => $materiaLabel,
                'id_clase' => (int) $classId,
            ],
            'detalle_clase' => [
                'id_clase' => (int) $clase->ID,
                'fecha' => !empty($clase->Fecha) ? Carbon::parse($clase->Fecha)->format('d/m/Y') : '',
                'titulo' => (string) $clase->Titulo,
                'descripcion' => (string) $clase->Guia_Ap,
                'visible' => $visibleClaseLabel,
                'id_visible' => (int) $clase->Estado,
                'fecha_visualizacion' => (!empty($clase->Fecha_Publicacion) && $clase->Fecha_Publicacion !== '0000-00-00')
                    ? Carbon::parse($clase->Fecha_Publicacion)->format('d/m/Y')
                    : '',
                'cantidades' => [
                    'tareas' => (int) $cantTareasClase,
                    'foros' => (int) $cantForosClase,
                    'recursos' => (int) $cantRecursosClase,
                ],
                'mi_lectura' => [
                    'leido' => (int) ($lectura->Leido ?? 0),
                    'datos_lectura' => ((int) ($lectura->Leido ?? 0) === 1)
                        ? (
                            (!empty($lectura->Fecha_Leido) && $lectura->Fecha_Leido !== '0000-00-00'
                                ? Carbon::parse($lectura->Fecha_Leido)->format('d/m/Y')
                                : ''
                            )
                            . ' a las ' . (string) ($lectura->Hora_Leido ?? '')
                        )
                        : '',
                ],
            ],
        ];
    }

    public function listarContenidosClaseAlumno($studentId, $materiaId, $tipoMateria, $classId, $institucionId, $cicloLectivo = null)
    {
        $alumno = Alumno::find($studentId);
        if (!$alumno) {
            return null;
        }

        $tipoMateria = strtolower(trim((string) $tipoMateria));
        if (!in_array($tipoMateria, ['c', 'g'], true)) {
            throw new \RuntimeException('Tipo de materia inválido.');
        }

        $ciclo = $this->resolveCicloAlumno($alumno, $cicloLectivo);
        if (!$ciclo) {
            return null;
        }

        $clase = $this->findClaseAlumno($studentId, $materiaId, $tipoMateria, $classId, (int) $ciclo->ID);
        if (!$clase) {
            return null;
        }

        $filesMeta = $this->resolveInstitutionFilesMeta((int) $institucionId);

        $rows = DB::table('clases_virtuales_contenidos as cvc')
            ->join('clases_virtuales_contenidos_tipos as cvct', 'cvc.ID_Tipo', '=', 'cvct.ID')
            ->leftJoin('clases_virtuales_contenidos_lecturas as l', function ($join) use ($studentId) {
                $join->on('cvc.ID', '=', 'l.ID_Contenido')
                    ->where('l.ID_Alumno', '=', $studentId);
            })
            ->where('cvc.ID_Clase', (int) $classId)
            ->where('cvc.ID_Materia', (int) $materiaId)
            ->where('cvc.Tipo_Materia', (string) $tipoMateria)
            ->where('cvc.Visible', 1)
            ->where('cvc.Estado', '<=', 1)
            ->orderBy('cvc.Orden', 'asc')
            ->orderBy('cvc.ID', 'asc')
            ->get([
                'cvc.ID',
                'cvc.ID_Tipo',
                'cvc.Orden',
                'cvc.Titulo',
                'cvc.Descripcion',
                'cvc.Enlace',
                'cvc.Duracion',
                'cvc.ID_Usuario',
                'cvc.Fecha',
                'cvc.Fecha_Vencimiento',
                'cvc.Estado',
                'cvc.Visible',
                'cvc.Servidor',
                'cvc.Archivo',
                'cvc.Code',
                'cvct.Tipo as NombreTipo',
                'cvct.Enlace as TipoEsEnlace',
                'l.ID as ID_Lectura',
            ]);

        $items = [];
        foreach ($rows as $r) {
            $archivo = trim((string) ($r->Archivo ?? ''));
            $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
            $leido = !empty($r->ID_Lectura);

            $tipoRecursoCodigo = 'archivo';
            if ((int) ($r->TipoEsEnlace ?? 0) === 1 || !empty($r->Enlace)) {
                $tipoRecursoCodigo = 'enlace';
            } elseif (in_array($extension, ['pdf'], true)) {
                $tipoRecursoCodigo = 'pdf';
            } elseif (in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'webm'], true)) {
                $tipoRecursoCodigo = 'video';
            }

            $items[] = [
                'id_recurso' => (int) $r->ID,
                'id_tipo_recurso' => (int) $r->ID_Tipo,
                'tipo_recurso' => (string) ($r->NombreTipo ?? ''),
                'tipo_recurso_codigo' => $tipoRecursoCodigo,
                'orden' => (int) $r->Orden,
                'titulo' => (string) $r->Titulo,
                'descripcion' => (string) $r->Descripcion,
                'enlace' => (string) ($r->Enlace ?? ''),
                'duracion' => (int) ($r->Duracion ?? 0),
                'id_usuario' => (int) ($r->ID_Usuario ?? 0),
                'fecha' => !empty($r->Fecha) ? Carbon::parse($r->Fecha)->format('Y-m-d') : null,
                'fecha_vencimiento' => (!empty($r->Fecha_Vencimiento) && $r->Fecha_Vencimiento !== '0000-00-00')
                    ? Carbon::parse($r->Fecha_Vencimiento)->format('Y-m-d')
                    : null,
                'estado' => (int) ($r->Estado ?? 0),
                'visible' => (int) ($r->Visible ?? 0),
                'servidor' => (int) ($r->Servidor ?? 0),
                'archivo' => $archivo,
                'documento' => $archivo !== '' ? ($filesMeta['base_tasks_url'] . $archivo) : null,
                'code' => (string) ($r->Code ?? ''),
                'leido' => $leido,
                'progreso' => $leido ? 100 : 0,
            ];
        }

        return [
            'id_materia' => (int) $materiaId,
            'tipo_materia' => strtoupper((string) $tipoMateria),
            'id_clase' => (int) $classId,
            'contenidos' => $items,
        ];
    }

    private function findClaseAlumno($studentId, $materiaId, $tipoMateria, $classId, $cicloId)
    {
        $hoy = Carbon::now('America/Argentina/Buenos_Aires')->format('Y-m-d');

        return DB::table('clases_virtuales as cv')
            ->join('clases_virtuales_envios as cve', function ($join) use ($studentId) {
                $join->on('cve.ID_Clase', '=', 'cv.ID')
                    ->where('cve.ID_Destinatario', '=', $studentId)
                    ->where('cve.Envio', '=', 1);
            })
            ->where('cv.ID', (int) $classId)
            ->where('cv.ID_Materia', (int) $materiaId)
            ->where('cv.Tipo_Materia', (string) $tipoMateria)
            ->where('cv.ID_Ciclo_Lectivo', (int) $cicloId)
            ->where('cv.Estado', 1)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('cv.Fecha_Publicacion')
                    ->orWhere('cv.Fecha_Publicacion', '0000-00-00')
                    ->orWhere('cv.Fecha_Publicacion', '<=', $hoy);
            })
            ->select(
                'cv.ID',
                'cv.Titulo',
                'cv.Guia_Ap',
                'cv.Estado',
                'cv.Orden',
                'cv.Fecha',
                'cv.Fecha_Publicacion'
            )
            ->first();
    }
    private function resolveMateriaLabelAlumno($materiaId, $tipoMateria)
    {
        if ($tipoMateria === 'c') {
            $row = DB::table('materias as mat')
                ->join('cursos as cur', 'mat.ID_Curso', '=', 'cur.ID')
                ->where('mat.ID', (int) $materiaId)
                ->select('mat.Materia', 'cur.Cursos')
                ->first();

            if (!$row) {
                throw new \RuntimeException('Materia no encontrada.');
            }

            return trim((string) $row->Materia) . ' (' . trim((string) $row->Cursos) . ')';
        }

        $row = DB::table('materias_grupales as mat')
            ->where('mat.ID', (int) $materiaId)
            ->select('mat.Materia')
            ->first();

        if (!$row) {
            throw new \RuntimeException('Materia grupal no encontrada.');
        }

        return trim((string) $row->Materia);
    }

    private function findForoAlumno($studentId, $forumId)
    {
        $hoy = Carbon::now('America/Argentina/Buenos_Aires')->format('Y-m-d');

        return DB::table('tareas_virtuales as tv')
            ->join('tareas_envios as te', function ($join) use ($studentId) {
                $join->on('te.ID_Tarea', '=', 'tv.ID')
                    ->where('te.ID_Destinatario', '=', $studentId)
                    ->where('te.Envio', '=', 1);
            })
            ->where('tv.ID', $forumId)
            ->where('tv.Tipo', 2)
            ->where('tv.Envio', 1)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('tv.Fecha_Publicacion')
                    ->orWhere('tv.Fecha_Publicacion', '0000-00-00')
                    ->orWhere('tv.Fecha_Publicacion', '<=', $hoy);
            })
            ->select(
                'tv.*',
                'te.Leido as Leido_Envio',
                'te.Resuelto as Resuelto_Envio',
                'te.Corregido as Corregido_Envio'
            )
            ->first();
    }

    private function resolveInstitutionFilesMeta($institucionId)
    {
        $institucion = DatabaseManager::getInstitutionData((int) $institucionId);

        $carpeta = trim((string) ($institucion->Carpeta ?? ''));
        $urlBase = rtrim((string) ($institucion->URL ?? ''), '/');

        if ($carpeta === '') {
            throw new \RuntimeException('No se pudo determinar la carpeta institucional.');
        }

        $ruta = (string) config('app.ruta_tareas');
        $ruta = str_replace('{carpeta}', $carpeta, $ruta);
        $ruta = rtrim($ruta, "\\/");

        if (!file_exists($ruta)) {
            @mkdir($ruta, 0755, true);
        }

        if (!file_exists($ruta)) {
            throw new \RuntimeException('No se pudo crear la carpeta destino para adjuntos.');
        }

        return [
            'filesystem_path' => $ruta,
            'base_tasks_url' => $urlBase . '/' . trim($carpeta, '/') . '/tareas/',
            'base_avatar_url' => $urlBase . '/' . trim($carpeta, '/') . '/imagenes/usuarios/',
        ];
    }

    private function resolveCicloAlumno($alumno, $cicloLectivo = null)
    {
        $cicloQuery = DB::table('ciclo_lectivo')
            ->where('ID_Nivel', $alumno->ID_Nivel);

        if (!empty($cicloLectivo)) {
            $ciclo = (clone $cicloQuery)
                ->where('Ciclo_lectivo', (int) $cicloLectivo)
                ->first();
        } else {
            $ciclo = null;
        }

        if (!$ciclo) {
            $ciclo = (clone $cicloQuery)
                ->where('Vigente', 'SI')
                ->orderByDesc('ID')
                ->first();
        }

        if (!$ciclo) {
            $ciclo = (clone $cicloQuery)
                ->orderByDesc('ID')
                ->first();
        }

        return $ciclo;
    }

    private function findTareaAlumno($studentId, $materiaId, $tipoMateria, $taskId, $cicloId)
    {
        $hoy = Carbon::now('America/Argentina/Buenos_Aires')->format('Y-m-d');

        return DB::table('tareas_virtuales as tv')
            ->join('tareas_envios as te', function ($join) use ($studentId) {
                $join->on('te.ID_Tarea', '=', 'tv.ID')
                    ->where('te.ID_Destinatario', '=', $studentId);
            })
            ->where('tv.ID', $taskId)
            ->where('tv.ID_Materia', $materiaId)
            ->where('tv.Tipo_Materia', $tipoMateria)
            ->where('tv.ID_Ciclo_Lectivo', $cicloId)
            ->where('tv.Tipo', 1)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('tv.Fecha_Publicacion')
                    ->orWhere('tv.Fecha_Publicacion', '0000-00-00')
                    ->orWhere('tv.Fecha_Publicacion', '<=', $hoy);
            })
            ->select(
                'tv.*',
                'te.Leido as Leido_Envio',
                'te.Resuelto as Resuelto_Envio',
                'te.Corregido as Corregido_Envio'
            )
            ->first();
    }

    private function resolveVencimientoTarea($alumno, $tarea, $cicloId)
    {
        $fecha = (!empty($tarea->Fecha_Vencimiento) && $tarea->Fecha_Vencimiento !== '0000-00-00')
            ? Carbon::parse($tarea->Fecha_Vencimiento)->format('Y-m-d')
            : null;

        $hora = !empty($tarea->Hora_Vencimiento) ? (string) $tarea->Hora_Vencimiento : null;

        if (!Schema::hasTable('tareas_virtuales_vencimientos')) {
            return ['fecha' => $fecha, 'hora' => $hora];
        }

        $idAgrupacion = $this->resolveAgrupacionAlumno($alumno, $cicloId);

        if ($idAgrupacion <= 0) {
            return ['fecha' => $fecha, 'hora' => $hora];
        }

        $override = TareaVirtualVencimiento::where('ID_Tarea', $tarea->ID)
            ->where('Tipo', 1)
            ->where('ID_Agrupacion', $idAgrupacion)
            ->first();

        if (!$override) {
            return ['fecha' => $fecha, 'hora' => $hora];
        }

        return [
            'fecha' => (!empty($override->Fecha_Vencimiento) && $override->Fecha_Vencimiento !== '0000-00-00')
                ? Carbon::parse($override->Fecha_Vencimiento)->format('Y-m-d')
                : $fecha,
            'hora' => !empty($override->Hora_Vencimiento) ? (string) $override->Hora_Vencimiento : $hora,
        ];
    }

    private function resolveAgrupacionAlumno($alumno, $cicloId)
    {
        $idGrupo = (int) ($alumno->ID_Grupo ?? 0);
        if ($idGrupo <= 0) {
            return 0;
        }

        $agr = Agrupacion::where('ID', $idGrupo)
            ->where('ID_Curso', $alumno->ID_Curso)
            ->where('ID_Ciclo_Lectivo', $cicloId)
            ->first();

        return $agr ? (int) $agr->ID : 0;
    }

    private function isTareaVencida($fecha, $hora)
    {
        if (empty($fecha)) {
            return false;
        }

        $horaFinal = !empty($hora) ? $hora : '23:59:59';
        $limite = Carbon::createFromFormat('Y-m-d H:i:s', $fecha . ' ' . $horaFinal, 'America/Argentina/Buenos_Aires');

        return Carbon::now('America/Argentina/Buenos_Aires')->gt($limite);
    }

    private function buildSafeFileName($nombreOriginal)
    {
        $nombreOriginal = trim((string) $nombreOriginal);
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $base = pathinfo($nombreOriginal, PATHINFO_FILENAME);
        $base = preg_replace('/[^A-Za-z0-9\-_]/', '_', $base);
        $base = preg_replace('/_+/', '_', $base);
        $base = trim($base, '_');

        if ($base === '') {
            $base = 'archivo';
        }

        return date('YmdHis') . '_' . substr(md5(uniqid((string) rand(), true)), 0, 6) . '_' . $base . '.' . $extension;
    }
}
