<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    protected $connection = 'tenant';

    protected $table = 'tareas_virtuales';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    // 🔥 CRÍTICO PARA LARAVEL 5.5: Definir las fechas para que sean objetos Carbon
    protected $dates = [
        'Fecha',
        'Fecha_Vencimiento'
    ];

    protected $fillable = [
        'ID_Materia',
        'Tipo_Materia',
        'ID_Curso',
        'ID_Clase',
        'ID_Usuario',
        'ID_Ciclo_Lectivo',
        'Titulo',
        'Tipo',
        'Fecha',
        'Fecha_Vencimiento',
        'Hora_Vencimiento',
        'Envio',
        'Cerrada',
        'Dest_Sel'
    ];

    protected $casts = [
        'Envio' => 'boolean',
        'Cerrada' => 'boolean',
        'Dest_Sel' => 'boolean'
    ];

    // --- RELACIONES CON SINTAXIS "VINTAGE" (Evita dolores de cabeza con namespaces) ---

    public function subject(): BelongsTo
    {
        if ($this->Tipo_Materia === 'g') {
            return $this->belongsTo('App\Models\SubjectGroup', 'ID_Materia', 'ID');
        }
        return $this->belongsTo('App\Models\Subject', 'ID_Materia', 'ID');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo('App\Models\Course', 'ID_Curso', 'ID');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo('App\Models\Teacher', 'ID_Usuario', 'ID');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo('App\Models\Cycle', 'ID_Ciclo_Lectivo', 'ID');
    }

    public function virtualClass(): BelongsTo
    {
        return $this->belongsTo('App\Models\VirtualClass', 'ID_Clase', 'ID');
    }

    public function resolutions(): HasMany
    {
        return $this->hasMany('App\Models\TaskResolution', 'ID_Tarea', 'ID');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany('App\Models\TaskSubmission', 'ID_Tarea', 'ID');
    }

    public function queries(): HasMany
    {
        return $this->hasMany('App\Models\TaskQuery', 'ID_Tarea', 'ID');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany('App\Models\Student', 'tareas_envios', 'ID_Tarea', 'ID_Destinatario', 'ID', 'ID');
    }

    // --- SCOPES ---

    public function scopeActive($query)
    {
        return $query->where('Envio', 1)->where('Cerrada', 0);
    }

    public function scopeForCycle($query, $cycleId)
    {
        return $query->where('ID_Ciclo_Lectivo', $cycleId);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->whereHas('students', function ($q) use ($studentId) {
            $q->where('ID_Destinatario', $studentId);
        })->orWhere('Dest_Sel', 0);
    }

    // --- ACCESSORS ---

    public function getTypeAttribute(): string
    {
        return $this->Tipo === 1 ? 'Tarea' : 'Foro';
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->Fecha_Vencimiento || $this->Fecha_Vencimiento->format('Y-m-d') === '0000-00-00') {
            return false;
        }
        return $this->Fecha_Vencimiento->isPast();
    }

    public function getDaysUntilDueAttribute(): int
    {
        if (!$this->Fecha_Vencimiento || $this->Fecha_Vencimiento->format('Y-m-d') === '0000-00-00') {
            return 999;
        }
        return now()->diffInDays($this->Fecha_Vencimiento, false);
    }

    public function getDueStatusAttribute(): string
    {
        if (!$this->Fecha_Vencimiento || $this->Fecha_Vencimiento->format('Y-m-d') === '0000-00-00') {
            return 'No posee';
        }

        $daysUntil = $this->days_until_due;

        if ($daysUntil < 0) {
            return 'Vencida';
        } elseif ($daysUntil === 0) {
            return 'Vence hoy';
        } elseif ($daysUntil === 1) {
            return 'Vence Mañana';
        } else {
            return '';
        }
    }

    public function getFormattedDueDateAttribute(): string
    {
        if (!$this->Fecha_Vencimiento || $this->Fecha_Vencimiento->format('Y-m-d') === '0000-00-00') {
            return 'No posee';
        }
        return $this->Fecha_Vencimiento->format('d/m/Y');
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->Fecha->format('d/m/Y');
    }
}