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
        if (app()->bound('Game'.ucfirst($country))) {
            // get newest batch ID by language
            $batch = BatchModel::where('Country', $country)
                ->orderBy('ID', 'DESC')
                ->first();
            if (empty($batch->ID)) { return false; }

            $model   = '\\App\\Models\\Game'.ucfirst($country);
            $games   = $model::with('price')->NeedSync()->get();
            $summary = new SummaryModel();
            foreach ($games as $game) {
                $game_data = [
                    'Title'         => $game->Title ?? '',
                    'GroupID'       => 0,
                    'GameID'        => $game->ID ?? '',
                    'Country'       => $country,
                    'Boxart'        => $game->Boxart ?? '',
                    'Price'         => $game->price->Price ?? 0.0,
                    'MSRP'          => $game->MSRP ?? 0.0,
                    'Discount'      => $this->calcDiscount($game),
                    'IsLowestPrice' => $this->isLowestPrice($game),
                    'UpdateTime'    => date('Y-m-d H:i:s')
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

    private function isLowestPrice($game)
    {
        if (empty($game->price->Price)|| empty($game->LowestPrice)) {
            return false;
        }
        return round($game->price->Price, 2) === round($game->LowestPrice, 2);
    }

    private function calcDiscount($game)
    {
        $price    = floatval($game->price->Price);
        $msrp     = floatval($game->MSRP);
        $discount = empty($msrp)? 0 : 1 - ($price / $msrp);
        return round($discount * 100);
    }
}