<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
// Usá la ruta completa para evitar confusiones
use App\Repositories\InscripcionRepository; 

class InscripcionController extends Controller
{
    protected $repo;

    // Cambiá la inyección para que sea explícita
    public function __construct(\App\Repositories\InscripcionRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getDisponibles(Request $request)
    {
        return response()->json(['status' => 'Llegaste al Controller!']);
    }
}