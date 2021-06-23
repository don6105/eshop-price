<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base as Model;

class PriceHk extends Model
{
    use HasFactory;

    public $incrementing = true;
    public $timestamps   = false;

    protected $table      = 'price_hk';
    protected $primaryKey = 'ID';
    protected $guarded    = ['ID'];
}
