<?php

namespace App\Http\Controllers;

use App\Repositories\CorrelativityRepository;
use Illuminate\Http\Request;

class CorrelativityController extends Controller
{
    protected $repo;

    public function __construct(CorrelativityRepository $repo) {
        $this->repo = $repo;
    }

    public function index($studentId)
    {
        $data = $this->repo->getFullPlan($studentId);

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Plan de correlatividades cargado.'
        ]);
    }
}