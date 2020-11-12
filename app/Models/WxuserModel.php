<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WxuserModel extends Model
{
    protected $table = "wxuser";
    protected $primaryKey = "id";
    public $timestamps = false;
    protected $guarded=[];
}

