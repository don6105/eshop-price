<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base as Model;

class PriceUs extends Model
{
    use HasFactory;

    protected $table = 'price_us';
    protected $primaryKey = 'ID';
}
