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
        $timestamp = strtotime($value);
        $datetime  = date('Y-m-d H:i:s', $timestamp);
        $this->attributes['ReleaseDate'] = $datetime;
    }

    public function setGenresAttribute($value)
    {
        $this->attributes['Genres'] = implode(', ', $value);
    }

    public function setPublishersAttribute($value)
    {
        $this->attributes['Publishers'] = implode(', ', $value);
    }

    public function setNSOAttribute($value)
    {
        $pattern = 'Nintendo Switch Online compatible';
        $this->attributes['NSO'] = in_array($pattern, $value)? 'Yes' : 'No';
    }

    public function setAvailabilityAttribute($value)
    {
        $this->attributes['Availability'] = implode(', ', $value);
    }

    public function setPlayer1Attribute($value)
    {
        $pattern = '1+';
        $this->attributes['Player1'] = in_array($pattern, $value)? 1 : 0;
    }

    public function setPlayer2Attribute($value)
    {
        $pattern = '2+';
        $this->attributes['Player2'] = in_array($pattern, $value)? 1 : 0;
    }

    public function setPlayer3Attribute($value)
    {
        $pattern = '3+';
        $this->attributes['Player3'] = in_array($pattern, $value)? 1 : 0;
    }

    public function setPlayer4Attribute($value)
    {
        $pattern = '4+';
        $this->attributes['Player4'] = in_array($pattern, $value)? 1 : 0;
    }

    public function scopeNotSync($query)
    {
        return $query->where('Sync', 0);
    }
}
