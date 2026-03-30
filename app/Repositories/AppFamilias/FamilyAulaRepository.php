<?php

namespace App\Repositories\AppFamilias;

use App\Models\Alumno;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FamilyAulaRepository
{
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

        $fechaInicioCiclo = !empty($ciclo->IPT) ? $ciclo->IPT : '2000-01-01';

        $recursos = DB::table('clases_virtuales_contenidos as cvc')
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
            ->orderBy('cvc.Fecha', 'desc')
            ->select(
                'cvc.*',
                'cvct.Tipo as NombreTipo',
                'cvct.Enlace as TipoEsEnlace',
                'l.ID as ID_Lectura'
            )
            ->get()
            ->map(function ($recurso) {
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
}
