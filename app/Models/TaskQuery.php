<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskQuery extends Model
{

    protected $connection = 'tenant';

    protected $table = 'tareas_consultas';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Tarea',
        'ID_Alumno',
        'Tipo',
        'ID_Usuario',
        'Consulta',
        'Fecha',
        'Leido',
        'Fecha_Leido',
        'Hora_Leido'
    ];

    protected $casts = [
        'ID_Tarea' => 'integer',
        'ID_Alumno' => 'integer',
        'ID_Usuario' => 'integer',
        'Fecha' => 'date',
        'Fecha_Leido' => 'date',
        'Hora_Leido' => 'time',
        'Leido' => 'boolean'
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'ID_Tarea', 'ID');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'ID_Alumno', 'ID');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ID_Usuario', 'id');
    }

    public function scopeByTask($query, $taskId)
    {
        return $query->where('ID_Tarea', $taskId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('ID_Alumno', $studentId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('ID_Usuario', $userId);
    }

    public function scopeUnread($query)
    {
        return $query->where('Leido', 0);
    }

    public function scopeRead($query)
    {
        return $query->where('Leido', 1);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('Tipo', $type);
    }
}
