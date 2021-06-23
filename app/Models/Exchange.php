<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base as Model;

class Exchange extends Model
{
    use HasFactory;

    public $incrementing = true;
    public $timestamps   = false;

    protected $table      = 'exchange';
    protected $primaryKey = 'ID';
    protected $guarded    = ['ID'];
}
