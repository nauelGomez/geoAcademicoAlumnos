<?php

namespace App\Http\Controllers;

use App\Repositories\AttendanceRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class AttendanceController extends Controller
{
    /** @var AttendanceRepository */
    private $attendanceRepository;

    public function __construct(AttendanceRepository $attendanceRepository)
    {
        $this->attendanceRepository = $attendanceRepository;
    }

    public function summary(Request $request, int $studentId): JsonResponse
    {
        try {
            $data = $this->attendanceRepository->getStudentSummary($studentId, $request->all());

            if (!$data) {
                return response()->json(['status' => 'error', 'message' => 'Alumno o ciclo no encontrado'], 404);
            }

            return response()->json(['status' => 'success', 'data' => $data], 200);

        } catch (Exception $e) {
            \Log::error('Error en AttendanceController@summary: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error al obtener resumen de asistencia.'], 500);
        }
    }

    public function subjectsAttendance(Request $request, int $studentId): JsonResponse
    {
        try {
            $data = $this->attendanceRepository->getSubjectsAttendance($studentId);

            if (!$data) {
                return response()->json(['status' => 'error', 'message' => 'Alumno o ciclo no encontrado'], 404);
            }

            return response()->json(['status' => 'success', 'data' => $data], 200);

        } catch (Exception $e) {
            \Log::error('Error en AttendanceController@subjectsAttendance: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error al obtener asistencia por materias.'], 500);
        }
    }

    public function subjectDetail(int $studentId, int $subjectId): JsonResponse
    {
        try {
            $data = $this->attendanceRepository->getSubjectDetail($studentId, $subjectId);

            if (!$data) {
                return response()->json(['status' => 'error', 'message' => 'Alumno o ciclo no encontrado'], 404);
            }

            return response()->json(['status' => 'success', 'data' => $data], 200);

        } catch (Exception $e) {
            \Log::error('Error en AttendanceController@subjectDetail: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error al obtener detalle de materia.'], 500);
        }
    }
}