<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WxMediaModel extends Model
{
    protected $table = "media";
    protected $primaryKey = "m_id";
    public $timestamps = false;
    protected $guarded=[];
}
