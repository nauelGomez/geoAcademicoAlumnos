<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LevelParameter extends Model
{
    protected $connection = 'tenant';

    protected $table = 'nivel_parametros';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'ID_Nivel',
        'Pub_Cal_NP'
    ];

    protected $casts = [
        'ID_Nivel' => 'integer',
        'Pub_Cal_NP' => 'integer'
    ];

    /**
     * Get the level
     */
    public function level()
    {
        return $this->belongsTo(Level::class, 'ID_Nivel', 'ID');
    }

    /**
     * Check if non-promediable grades should be published
     */
    public function shouldPublishNonPromediable()
    {
        return $this->Pub_Cal_NP == 1;
    }

    /**
     * Scope to get parameters for a specific level
     */
    public function scopeForLevel($query, $levelId)
    {
        return $query->where('ID_Nivel', $levelId);
    }
}
