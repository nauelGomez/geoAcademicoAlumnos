<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatGroup extends Model
{
    protected $connection = 'tenant';

    protected $table = 'chat_grupos';
    protected $primaryKey = 'ID';
    public $timestamps = false;
}
