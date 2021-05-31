<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base as Model;

class Language extends Model
{
    use HasFactory;

    const CREATED_AT = 'CreateTime';
    const UPDATED_AT = 'UpdateTime';

    public $incrementing = true;

    protected $table      = 'language_support';
    protected $primaryKey = 'ID';
    protected $fillable   = ['NameEny', 'NameTw'];
}
