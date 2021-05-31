<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base as Model;

class GameLangR extends Model
{
    use HasFactory;

    const CREATED_AT = 'CreateTime';
    const UPDATED_AT = 'UpdateTime';

    public $incrementing = true;

    protected $table      = 'game_language_relation';
    protected $primaryKey = 'ID';
    protected $fillable   = ['GameID', 'LanguageID', 'Status'];
}
