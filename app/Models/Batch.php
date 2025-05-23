<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base as Model;

class Batch extends Model
{
    use HasFactory;

    public $incrementing = true;
    public $timestamps   = false;

    protected $connection = 'mysql';
    protected $table      = 'batch';
    protected $primaryKey = 'ID';
    protected $guarded    = ['ID'];
}
