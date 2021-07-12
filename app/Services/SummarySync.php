<?php

namespace App\Services;

use App\Services\Base         as BaseService;
use App\Models\Exchange       as ExchangeModel;
use App\Models\Summary        as SummaryModel;
use App\Contracts\SummarySync as SummarySyncContract;

class SummarySync extends BaseService implements SummarySyncContract
{
    public function syncSummaryInfo($country):Bool
    {
        $sync_num = 0;
        $country  = empty($country)? '' : strtolower($country);
        if (class_exists('\\App\Models\\Game'.ucfirst($country))) {
            $model   = '\\App\\Models\\Game'.ucfirst($country);
            $games   = $model::with('price')->NeedSync()->get();
            $summary = new SummaryModel();
            foreach ($games as $game) {
                $price  = $game->price->Price ?? 0.0;
                $msrp   = $game->MSRP ?? 0.0;
                $lowest = $game->LowestPrice?? 0.0;
                $game_data = [
                    'Title'         => $game->Title ?? '',
                    'GameID'        => $game->ID ?? '',
                    'Country'       => $country,
                    'Boxart'        => $game->Boxart ?? '',
                    'Price'         => $this->changeToNTD($country, $price),
                    'MSRP'          => $this->changeToNTD($country, $msrp),
                    'LowestPrice'   => $this->changeToNTD($country, $lowest),
                    'IsGroupPrice'  => 0,
                    'UpdateTime'    => date('Y-m-d H:i:s')
                ];
                $summary->insertOrUpdate($game_data);
                $game->Sync = 1;
                $game->save();
                ++$sync_num;
                $this->progressBar($games->count());
            }
        }
        return $sync_num;
    }



    private function changeToNTD($country, $value):Float
    {
        static $exchange;
        if (!isset($exchange[$country])) {
            $rate = ExchangeModel::select('Rate')
                    ->where('country', $country)
                    ->first();
            $exchange[$country] = $rate->Rate;
        }
        return floatval($value) * $exchange[$country];
    }
}