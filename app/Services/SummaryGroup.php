<?php

namespace App\Services;

use App\Libraries\WikiGame     as WikiGameLib;
use App\Services\Base          as BaseService;
use App\Models\Summary         as SummaryModel;
use App\Contracts\SummaryGroup as SummaryGroupContract;

class SummaryGroup extends BaseService implements SummaryGroupContract
{
    public function setGameGroup():Int
    {
        $pending    = $this->getSummaryData();
        $pending    = $this->formatGameName($pending);
        $group_list = $this->findGameGroup($pending);
        $group_list = $this->filterGroupList($group_list);
        $group_list = $this->sortGroupList($group_list);
        $group_list = $this->assignOrderID($group_list);
        $succ_count = $this->updateSummary($group_list);
        return $succ_count;
    }



    private function getSummaryData():Array
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

    private function findGameGroup(Array $pendingList):Array
    {
        $WikiGame = new WikiGameLib();
        $WikiGame->loadGameList();

        $group_list = [];
        $last       = end($pendingList);
        $group_id   = $last['GroupID']?? 0;
        
        while (true) {
            $curr = array_shift($pendingList);
            if (!isset($curr)) { break; }

            $games = $WikiGame->findGameGroup($curr['Title']);
            array_push($games, $curr['Title']);

            foreach ($games as $game) {
                $key = $this->findTitleInArray($group_list, $game);
                if (isset($key)) {
                    if ($group_list[$key]['GroupID'] == 0) {
                        $group_list[$key]['GroupID'] = ++$group_id;
                    }
                    $curr['GroupID'] = $group_list[$key]['GroupID'];
                    break;
                }
            }
            array_push($group_list, $curr);
        }
        return $group_list;
    }

    private function filterGroupList(Array $groupList):Array
    {
        $group_list = array_filter($groupList, function($g) {
            return !empty($g['GroupID']);
        });
        return $group_list;
    }

    private function sortGroupList(Array $groupList):Array
    {
        $order = ['au', 'us', 'hk']; // hk > us > other
        $group_list = $groupList;
        usort($group_list, function($a, $b) use($order) {
            if ($a['GroupID'] === $b['GroupID']) {
                $a = array_search($a['Country'], $order);
                $b = array_search($b['Country'], $order);
                return $b - $a;
            }
            return $a['GroupID'] - $b['GroupID'];
        });
        return $group_list;
    }

    private function assignOrderID(Array $groupList):Array
    {
        if (empty($groupList)) { return []; }

        $group_list = [];
        $group_id   = 0;
        $order_id   = 1;
        foreach ($groupList as $row) {
            if ($row['GroupID'] !== $group_id) {
                $group_id = $row['GroupID'];
                $order_id = 1;
            }
            $row['OrderID'] = $order_id++;
            array_push($group_list, $row);
        }
        return $group_list;
    }

    private function updateSummary(Array $groupList):Int
    {
        if (empty($groupList)) { return 0; }

        $succ_count = 0;
        foreach ($groupList as $row) {
            $succ = SummaryModel::where('ID', $row['ID'])
                    ->where('IsManual', 0)
                    ->update([
                        'GroupID' => $row['GroupID'],
                        'OrderID' => $row['OrderID']
                    ]);
            if ($succ) { ++$succ_count; }
        }
        return $succ_count;
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