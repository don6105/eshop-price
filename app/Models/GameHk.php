<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base as Model;
use DateTime;

class GameHk extends Model
{
    use HasFactory;

    const CREATED_AT = 'CreateTime';
    const UPDATED_AT = 'UpdateTime';

    public $incrementing = true;

    protected $table      = 'game_hk';
    protected $primaryKey = 'ID';
    protected $guarded    = ['ID'];

    public function price()
    {
        return $this->hasOne('App\Models\PriceHk', 'GameID')->latest('ID');
    }

    public function setReleaseDateAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['ReleaseDate'] = DateTime::createFromFormat('y.m.d', $value)->format('Y-m-d');
        } else {
            $this->attributes['ReleaseDate'] = null;
        }
    }

    public function scopeNeedSync($query)
    {
        return $query->where('Sync', 0);
    }
}
