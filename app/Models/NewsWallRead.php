<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsWallRead extends Model
{
    protected $connection = 'tenant';

    protected $table = 'tareas_materia_muro_lecturas';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Muro_Detalle',
        'ID_Alumno'
    ];

    protected $casts = [
        'ID_Muro_Detalle' => 'integer',
        'ID_Alumno' => 'integer'
    ];

    /**
     * Get the news wall detail
     */
    public function newsWallDetail()
    {
        return $this->belongsTo(NewsWallDetail::class, 'ID_Muro_Detalle', 'ID');
    }

    /**
     * Get the student
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'ID_Alumno', 'ID');
    }

    /**
     * Mark a detail as read by a student
     */
    public static function markAsRead($muroDetalleId, $studentId)
    {
        return self::firstOrCreate([
            'ID_Muro_Detalle' => $muroDetalleId,
            'ID_Alumno' => $studentId
        ]);
    }

    /**
     * Check if a detail is read by a student
     */
    public static function isReadByStudent($muroDetalleId, $studentId)
    {
        return self::where('ID_Muro_Detalle', $muroDetalleId)
            ->where('ID_Alumno', $studentId)
            ->exists();
    }
}
