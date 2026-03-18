<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateType extends Model
{
    protected $connection = 'tenant';

    protected $table = 'certificaciones';
    protected $primaryKey = 'ID';
    public $timestamps = false;
}
