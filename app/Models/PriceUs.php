<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base as Model;

class PriceUs extends Model
{
    use HasFactory;

    public $incrementing = true;
    public $timestamps   = false;

    protected $table      = 'price_us';
    protected $primaryKey = 'ID';
    protected $fillable   = ['BatchID', 'GameID', 'Price'];
}
