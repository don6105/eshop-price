<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base as Model;

class GameUs extends Model
{
    use HasFactory;

    const CREATED_AT = 'CreateTime';
    const UPDATED_AT = 'UpdateTime';

    public $incrementing = true;

    protected $connection = 'mysql';
    protected $table      = 'game_us';
    protected $primaryKey = 'ID';
    protected $guarded    = ['ID'];

    public function price()
    {
        return $this->hasOne('App\Models\PriceUs', 'GameID')->latest('ID');
    }

    public function setURLAttribute($value)
    {
        $value = empty($value) ?: 'https://www.nintendo.com'.$value;
        $this->attributes['URL'] = $value;
    }

    public function setReleaseDateAttribute($value)
    {
        $datetime = null;
        if (!empty($value)) {
            $timestamp = strtotime($value);
            $datetime  = date('Y-m-d H:i:s', $timestamp);
        } else {
            $datetime = null;
        }
        $this->attributes['ReleaseDate'] = $datetime;
    }

    public function setGenresAttribute($value)
    {
        $this->attributes['Genres'] = implode(', ', $value);
    }

    public function setNSOAttribute($value)
    {
        $pattern = 'Nintendo Switch Online compatible';
        $this->attributes['NSO'] = in_array($pattern, $value)? 'Yes' : 'No';
    }

    public function setNumOfPlayersAttribute($value)
    {
        $player_num = preg_replace('/\D+/im', '', $value);
        $player_num = is_numeric($player_num)? $player_num : -1;
        $this->attributes['NumOfPlayers'] = $player_num;
    }

    public function setPublishersAttribute($value)
    {
        $this->attributes['Publishers'] = implode(', ', $value);
    }

    public function scopeNeedSync($query)
    {
        return $query->where('Sync', 0);
    }
}
