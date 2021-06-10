<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base as Model;

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
        $this->attributes['ReleaseDate'] = $value;
    }

    public function scopeNeedSync($query)
    {
        return $query->where('Sync', 0);
    }
}
