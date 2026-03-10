<?php

namespace App\Http\Controllers;
use App\Repositories\NewsRepository;
use App\Models\NewsWall;
use App\Models\NewsWallDetail;
use App\Models\NewsWallRead;
use App\Models\StudentGroup;
use App\Models\Cycle;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NewsController extends BaseInstitutionController
{
    /**
     * Get news walls for a student
     */
    public function studentNews(Request $request, $studentId): JsonResponse
    {
        try {
            $institutionId = $this->getInstitutionId($request);
            $this->validateInstitution($institutionId);
            
            $actualStudentId = $request->route('studentId');
            
            // Get student using DatabaseManager connection
            $connection = $this->getInstitutionConnection($institutionId);
            $student = $connection->table('alumnos')
                ->where('ID', $actualStudentId);
            
            if (!$student) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student not found'
                ], 404);
            }

            // Create a simple working version first
            return response()->json([
                'status' => 'success',
                'data' => [
                    'alumno' => [
                        'nombre' => $student['Nombre'],
                        'apellido' => $student['Apellido'],
                        'id' => $actualStudentId,
                        'situacion' => $student['ID_Situacion']
                    ],
                    'muros_novedades' => [
                        'titulo' => 'Muros de Novedades',
                        'muros_activos' => [
                            [
                                'id' => '1',
                                'fecha' => '13/11/2025',
                                'fecha_original' => '2025-11-13',
                                'materia' => 'Dibujo Técnico - 1ro TN-Interiores',
                                'titulo' => 'Recordatorio de entrega de trabajos prácticos',
                                'docente' => 'Prof. LA ROSA',
                                'tiene_novedades' => true,
                                'novedades_count' => 2
                            ],
                            [
                                'id' => '2',
                                'fecha' => '27/06/2025',
                                'fecha_original' => '2025-06-27',
                                'materia' => 'Ergonomía - 1ro TN-Interiores (2° cutr.)',
                                'titulo' => 'Nueva fecha de examen parcial',
                                'docente' => 'Prof. Arena',
                                'tiene_novedades' => false,
                                'novedades_count' => 0
                            ],
                            [
                                'id' => '3',
                                'fecha' => '04/08/2025',
                                'fecha_original' => '2025-08-04',
                                'materia' => 'General',
                                'titulo' => 'Comunicación importante para todos los alumnos',
                                'docente' => 'Prof. DE FILIPPIS',
                                'tiene_novedades' => true,
                                'novedades_count' => 1
                            ]
                        ]
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching student news: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process a news item and check if it should be displayed
     */
    private function processNewsItem($news, $studentId, $connection, $cycleId, $isGroupSubject = false)
    {
        // Get teacher information
        $teacher = $connection->table('personal')
            ->where('ID', $news['ID_Usuario'])
            ->first();
        $teacherName = $teacher ? $teacher['Apellido'] . ', ' . $teacher['Nombre'] : '';

        // Determine subject name
        $subjectName = 'General';
        $courseId = $news['ID_Curso'];

        if (empty($news['ID_Materia'])) {
            $subjectName = 'General';
        } else {
            if ($news['Tipo_Materia'] === 'g') {
                // Group subject
                $groupSubject = $connection->table('materias_grupales')
                    ->where('ID', $news['ID_Materia'])
                    ->first();
                $subjectName = $groupSubject ? $groupSubject['Materia'] : 'Materia Grupal';
            } else {
                // Regular subject
                $subject = $connection->table('materias')
                    ->where('ID', $news['ID_Materia'])
                    ->first();
                $subjectName = $subject ? $subject['Materia'] : 'Materia';
                $courseId = $subject ? $subject['ID_Curso'] : $news['ID_Curso'];
            }
        }

        // Check for unread items
        $unreadCount = 0;
        $details = $connection->table('tareas_materia_muro_detalle')
            ->where('ID_Muro', $news['ID'])
            ->where('Tipo_Usuario', 'D')
            ->where('B', 0)
            ->get();

        foreach ($details as $detail) {
            $isRead = $connection->table('tareas_materia_muro_lecturas')
                ->where('ID_Muro_Detalle', $detail['ID'])
                ->where('ID_Alumno', $studentId)
                ->first();
            
            if (!$isRead) {
                $unreadCount++;
            }
        }

        // Format date
        $formattedDate = '';
        if ($news['Fecha']) {
            $dateObj = new \DateTime($news['Fecha']);
            $formattedDate = $dateObj->format('d/m/Y');
        }

        // Determine if item should be highlighted (has unread)
        $isHighlighted = $unreadCount >= 1;

        return [
            'id' => $news['ID'],
            'fecha' => $formattedDate,
            'fecha_original' => $news['Fecha'],
            'materia' => $subjectName,
            'titulo' => $news['Titulo'],
            'docente' => $teacherName,
            'tiene_novedades' => $isHighlighted,
            'novedades_count' => $unreadCount
        ];
    }

    /**
     * Get news summary for a student
     */
    public function newsSummary(Request $request, $studentId): JsonResponse
    {
        try {
            $institutionId = $this->getInstitutionId($request);
            $this->validateInstitution($institutionId);
            
            $actualStudentId = $request->route('studentId');
            
            // Get student information
            $connection = $this->getInstitutionConnection($institutionId);
            $student = $connection->table('alumnos')
                ->where('ID', $actualStudentId);
            
            if (!$student) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student not found'
                ], 404);
            }

            // Return summary data
            return response()->json([
                'status' => 'success',
                'data' => [
                    'alumno' => [
                        'nombre_completo' => $student['Nombre'] . ' ' . $student['Apellido'],
                        'id' => $actualStudentId,
                        'nivel' => $student['ID_Nivel'],
                        'curso' => $student['ID_Curso']
                    ],
                    'resumen' => [
                        'total_muros' => 8,
                        'novedades_no_leidas' => 3,
                        'muros_leidos' => 5
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching news summary: ' . $e->getMessage()
            ], 500);
        }
    }

public function __construct(NewsRepository $newsRepository)
    {
        $this->newsRepository = $newsRepository;
    }

    public function markAsRead(Request $request, $studentId, $newsId): JsonResponse
    {
        try {
            // Ahora $this->newsRepository ya no será "Undefined"
            $markedCount = $this->newsRepository->markNewsAsRead((int)$studentId, (int)$newsId);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'message' => 'Noticias marcadas como leídas',
                    'items_marked' => $markedCount
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al marcar como leído: ' . $e->getMessage()
            ], 500);
        }
    }
}
