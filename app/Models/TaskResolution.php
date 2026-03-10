<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskResolution extends Model
{
    // Conexión dinámica configurada por el Middleware
    protected $connection = 'tenant';

    // Tabla según tu estructura de HeidiSQL
    protected $table = 'tareas_resoluciones';
    
    protected $primaryKey = 'ID';
    
    // Desactivamos timestamps automáticos (created_at/updated_at)
    public $timestamps = false;

    protected $fillable = [
        'ID_Tarea',
        'ID_Alumno',
        'Resolucion',
        'Fecha',
        'Hora',
        'Leido',
        'Fecha_Leido',
        'Hora_Leido',
        'Correcion',
        'Comentario_Correccion',
        'Fecha_Correccion',
        'Hora_Correccion'
    ];

    protected $casts = [
        'Fecha'            => 'date',
        'Fecha_Leido'      => 'date',
        'Fecha_Correccion' => 'date',
        'Leido'            => 'boolean',
        'Correcion'        => 'boolean',
        'ID_Tarea'         => 'integer',
        'ID_Alumno'        => 'integer',
        // 'Hora' => 'time' -> ELIMINADO: No existe en Laravel 7.
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function task()
    {
        return $this->belongsTo(Task::class, 'ID_Tarea', 'ID');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'ID_Alumno', 'ID');
    }

    // ==========================================
    // ACCESSORS (Getters)
    // ==========================================

    /**
     * Devuelve el estado legible de la corrección.
     * Uso: $resolution->status
     */
    public function getStatusAttribute()
    {
        if (!$this->ID) {
            return 'Pendiente';
        }

        if (!$this->Correcion) {
            return 'Pendiente de Evaluación';
        }

        return 'Evaluado';
    }

    // ==========================================
    // SCOPES (Filtros en el Repositorio)
    // ==========================================

    public function scopeByTask($query, $taskId)
    {
        return $query->where('ID_Tarea', $taskId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('ID_Alumno', $studentId);
    }

    public function scopeCorrected($query)
    {
        return $query->where('Correcion', 1);
    }

    public function scopePending($query)
    {
        return $query->where('Correcion', 0);
    }
}