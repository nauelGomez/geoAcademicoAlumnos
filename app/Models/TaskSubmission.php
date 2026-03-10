<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskSubmission extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'tareas_envios';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Tarea',
        'ID_Destinatario',
        'Aleatorio',
        'Envio',
        'Leido',
        'Fecha_Leido',
        'Hora_Leido',
        'IP_Leido',
        'MailD',
        'Resuelto',
        'Corregido'
    ];

    protected $casts = [
        'ID_Tarea' => 'integer',
        'ID_Destinatario' => 'integer',
        'Envio' => 'boolean',
        'Leido' => 'boolean',
        'Fecha_Leido' => 'date',
        'Resuelto' => 'boolean',
        'Corregido' => 'boolean'
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'ID_Tarea', 'ID');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'ID_Destinatario', 'ID');
    }

    public function scopeByTask($query, $taskId)
    {
        return $query->where('ID_Tarea', $taskId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('ID_Destinatario', $studentId);
    }

    public function scopeRead($query)
    {
        return $query->where('Leido', 1);
    }

    public function scopeUnread($query)
    {
        return $query->where('Leido', 0);
    }
}
