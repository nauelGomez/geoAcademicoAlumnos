<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Subject;

class Level extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'nivel';
    protected $primaryKey = 'ID';
    public $timestamps = false;

    protected $fillable = [
        'Nivel',
        'Numero',
        'CUE',
        'Sello_institucion',
        'Sello_Director',
        'Firma_Director'
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'ID_Nivel', 'ID');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'ID_Nivel', 'ID');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'ID_Nivel', 'ID');
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(Cycle::class, 'ID_Nivel', 'ID');
    }

    public function multipleLevels(): HasMany
    {
        return $this->hasMany(NivelMultiple::class, 'ID_Nivel', 'ID');
    }
}
