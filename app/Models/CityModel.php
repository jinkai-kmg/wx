<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CityModel extends Model
{
    protected $table = "city";
    protected $primaryKey = "id";
    public $timestamps = false;
    protected $guarded=[];
}
