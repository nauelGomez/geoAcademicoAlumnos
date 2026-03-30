<?php

namespace App\Repositories;

use App\Models\Alumno;
use App\Models\PlanEstudio;
use Illuminate\Support\Facades\DB;

class CorrelativityRepository
{
    public function getFullPlan($studentId)
    {
        $alumno = Alumno::with('curso')->findOrFail($studentId);

        // Si no es regular, es egresado según el PHP original
        if ($alumno->ID_Situacion != 2) {
            return ['status' => 'Egresado', 'plan' => null];
        }

        $idPlan = $alumno->curso->ID_Plan;

        // Traemos todo el árbol: Plan -> Años -> Materias -> Correlativas -> Nombre de Correlativa
        $plan = PlanEstudio::with([
            'cursos' => function($q) { $q->orderBy('Orden'); },
            'cursos.materias' => function($q) { $q->orderBy('Orden'); },
            'cursos.materias.correlativas.materiaRequerida'
        ])->find($idPlan);

        return [
            'alumno' => $alumno->Nombre . ' ' . $alumno->Apellido,
            'curso_actual' => $alumno->curso->Cursos ?? 'N/A',
            'plan_nombre' => $plan->Nombre ?? 'Sin Plan Asignado',
            'estructura' => $plan ? $this->formatPlan($plan) : []
        ];
    }

    private function formatPlan($plan)
    {
        return $plan->cursos->map(function($ano) {
            return [
                'nivel_nombre' => $ano->Curso,
                'materias' => $ano->materias->map(function($mat) {
                    return [
                        'nombre' => $mat->Materia,
                        'requiere_cursada' => $mat->correlativas->where('Tipo', 1)->map(function($c) {
                            return $c->materiaRequerida->Materia ?? 'Materia desconocida';
                        })->values(),
                        'requiere_final' => $mat->correlativas->where('Tipo', 2)->map(function($c) {
                            return $c->materiaRequerida->Materia ?? 'Materia desconocida';
                        })->values(),
                    ];
                })
            ];
        });
    }
}
