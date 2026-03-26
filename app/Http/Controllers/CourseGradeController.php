<?php

namespace App\Http\Controllers;

use App\Repositories\CourseGradeRepository;
use Illuminate\Http\Request;

class CourseGradeController extends BaseInstitutionController
{
    protected $repo;

    public function __construct(CourseGradeRepository $repo)
    {
        $this->repo = $repo;
    }

    public function studentGrades($studentId)
    {
        try {
            $data = $this->repo->getGrades($studentId);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al cargar las notas.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}