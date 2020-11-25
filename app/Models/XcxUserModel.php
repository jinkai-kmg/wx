<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class XcxUserModel extends Model
{
    protected $table = "xcx_users";
    protected $primaryKey = "xcx_u_id";
    public $timestamps = false;
    protected $guarded=[];
}
