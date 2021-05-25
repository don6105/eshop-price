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

            $model_name = '\\App\\Models\\Game'.ucfirst($country);
            $games   = $model_name::with('price')->NeedSync()->get();
            $summary = new SummaryModel();
            foreach ($games as $game) {
                $game_data = [
                    'Title'         => $game->Title ?? '',
                    'GroupID'       => 0,
                    'GameID'        => $game->ID ?? '',
                    'Country'       => $country,
                    'Boxart'        => $game->Boxart ?? '',
                    'Price'         => $game->price->Price ?? '',
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
        $discount = floatval($game->price->Price) / floatval($game->MSRP);
        $discount = 1 - $discount;
        return round($discount * 100);
    }
}