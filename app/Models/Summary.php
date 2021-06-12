<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base as Model;

class Summary extends Model
{
    use HasFactory;

    public $incrementing = true;
    public $timestamps   = false;

    protected $table      = 'summary';
    protected $primaryKey = 'ID';
    protected $fillable   = [''];
}
