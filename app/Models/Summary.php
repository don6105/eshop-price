<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;
use App\Models\Base as Model;

class Summary extends Model
{
    use HasFactory;

    public $incrementing = true;
    public $timestamps   = false;

    protected $table      = 'summary';
    protected $primaryKey = 'ID';
    protected $guarded    = ['ID'];

    public function game()
    {
        $relationships = $this->getRelationWithGame();
        Relation::morphMap($relationships);
        return $this->morphTo(__FUNCTION__, 'Country', 'GameID');
    }

    private function getRelationWithGame()
    {
        // get dynamic relationship by reading path of models
        // ex. ['us' => 'App\Models\GameUs', ... ]
        $cache = Cache::store('file');

        try {
            if ($cache->has(__FUNCTION__)) {
                return $cache->get(__FUNCTION__);
            }
        } catch(\Throwable $t) {}
        
        $relationships = [];
        foreach (scandir(app_path('Models')) as $model) {
            if (strpos($model, 'Game') === false) { continue; }
            if (preg_match('/Game([A-Za-z]{2})/', $model, $m) > 0) {
                $country = strtolower($m[1]);
                $model   = 'App\\Models\\Game'.ucfirst($country);
                $relationships[ $country ] = $model;
            }
        }
        try {
            $cache->put(__FUNCTION__, $relationships, now()->addHours(12));
        } catch(\Throwable $t) {}
        return $relationships;
    }
}
