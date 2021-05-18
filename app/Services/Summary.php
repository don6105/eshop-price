<?php

namespace App\Services;

use App\Contracts\Summary as SummaryContract;
use App\Services\Base as BaseService;
use App\Models\Batch as BatchModel;
use App\Models\Summary as SummaryModel;

class Summary extends BaseService implements SummaryContract
{
    public function getGameData($country)
    {
        $country = empty($country)? '' : strtolower($country);
        if (app()->bound('Game').ucfirst($country)) {
            // get newest batch ID by language
            $batch = BatchModel::where('Country', $country)
                ->orderBy('ID', 'DESC')
                ->first();
            if (empty($batch->ID)) { return false; }
            $batch_id = $batch->ID;

            $model_name = '\\App\\Models\\Game'.ucfirst($country);
            $games   = $model_name::with('price')->NotSync()->get();
            $summary = new SummaryModel();
            foreach ($games as $game) {
                $discount  = floatval($game->price->Price) / floatval($game->MSRP) * 100;
                $game_data = [
                    'Title'        => $game->Title ?? '',
                    'GroupID'      => 0,
                    'GameID'       => $game->ID ?? '',
                    'Country'      => $country,
                    'Boxart'       => $game->Boxart ?? '',
                    'Price'        => $game->price->Price ?? '',
                    'Discount'     => round($discount),
                    'UpdateTime'   => date('Y-m-d H:i:s')
                ];
                $summary->insertOrUpdate($game_data);
                $game->Sync = 1;
                $game->save();
                $this->progressBar($games->count());
            }
            return true;
        }
        return false;
    }
}