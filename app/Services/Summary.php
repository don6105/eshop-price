<?php

namespace App\Services;

use App\Models\Batch       as BatchModel;
use App\Libraries\WikiGame as WikiGameLib;
use App\Services\Base      as BaseService;
use App\Models\Summary     as SummaryModel;
use App\Contracts\Summary  as SummaryContract;

class Summary extends BaseService implements SummaryContract
{
    public function syncGameInfo($country)
    {
        $country = empty($country)? '' : strtolower($country);
        if (class_exists('\\App\Models\\Game'.ucfirst($country))) {
            $batch_id = $this->getLastBatchID($country);
            if (empty($batch_id)) { return false; }

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

    public function setGameGroup()
    {
        $WikiGame = new WikiGameLib();
        $WikiGame->loadGameList();

        $grouped = [];
        $pending = $this->getSummaryData();
        $pending = $this->formatGameName($pending);

        $last     = end($pending);
        $group_id = $last['GroupID']?? 0;
        
        while (true) {
            $curr = array_shift($pending);
            if (!isset($curr)) { break; }

            $games = $WikiGame->findGameGroup($curr['Title']);
            array_push($games, $curr['Title']);

            foreach ($games as $game) {
                $key = $this->findTitleInArray($grouped, $game);
                if (isset($key)) {
                    if ($grouped[$key]['GroupID'] == 0) {
                        $grouped[$key]['GroupID'] = ++$group_id;
                    }
                    $curr['GroupID'] = $grouped[$key]['GroupID'];
                    break;
                }
            }
            array_push($grouped, $curr);
        }
        $grouped = array_filter($grouped, function($g) {
            return !empty($g['GroupID']);
        });
        $order = ['us', 'hk']; // hk > us > other
        usort($grouped, function($a, $b) use($order) {
            if ($a['GroupID'] === $b['GroupID']) {
                $a = array_search($a['Country'], $order);
                $b = array_search($b['Country'], $order);
                return $b - $a;
            }
            return $a['GroupID'] - $b['GroupID'];
        });
        dd($grouped);
    }

    private function getLastBatchID($country)
    {
        $batch = BatchModel::where('Country', $country)
                ->orderBy('ID', 'DESC')
                ->first();
        return $batch->ID?: 0;
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
        $price    = isset($game->price->Price);
        $msrp     = isset($game->MSRP);
        $discount = empty($msrp)? 0 : 1 - (floatval($price) / floatval($msrp));
        return round($discount * 100);
    }

    private function getSummaryData()
    {
        $data = SummaryModel::select('ID', 'Title','GroupID', 'OrderID', 'Country')
                    ->orderBy('GroupID', 'ASC')
                    ->get()
                    ->toArray();
        return $data;
    }

    private function formatGameName(Array $game):Array
    {
        if (empty($game)) { return []; }
        foreach ($game as $key => $row) {
            $row = str_replace(['™', '®'], '', $row);
            $game[$key] = $row;
        }
        return $game;
    }

    private function findTitleInArray(Array $array, $title)
    {
        if (empty($array)) { return null; }

        foreach ($array as $key => $row) {
            if ($row['Title'] === $title) {
                return $key;
            }
        }
        return null;
    }
}