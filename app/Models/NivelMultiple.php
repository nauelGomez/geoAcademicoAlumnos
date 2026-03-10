<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NivelMultiple extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'niveles_multiples';
    protected $primaryKey = 'IDPrimary';
    public $timestamps = false;

    protected $fillable = [
        'ID_Usuario',
        'ID_Nivel',
        'Rol'
    ];

    protected $casts = [
        'ID_Usuario' => 'integer',
        'ID_Nivel' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ID_Usuario', 'id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class, 'ID_Nivel', 'IDPrimary');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('ID_Usuario', $userId);
    }

    public function scopeByLevel($query, $levelId)
    {
        return $query->where('ID_Nivel', $levelId);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('Rol', $role);
    }
}
