<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use App\Services\Base          as BaseService;
use App\Models\Summary         as SummaryModel;
use App\Models\WikiGame        as WikiGameModel;
use App\Contracts\SummaryGroup as SummaryGroupContract;

class SummaryGroup extends BaseService implements SummaryGroupContract
{
    public function __get($value)
    {
        if (strcasecmp($value, 'wiki_game') === 0) {
            $this->wiki_game = $this->loadGameGroup(false);
            
            // pull from wiki website & reload
            if(empty($this->wiki_game)) {
                Artisan::call('wikigame:pull');
                $this->wiki_game = $this->loadGameGroup(true);
            }
            return $this->wiki_game;
        }
    }

    public function setSummaryGroup():Int
    {
        $pending    = $this->getSummaryData();
        $pending    = $this->formatGameName($pending);
        $group_list = $this->computeGameGroup($pending);
        $group_list = $this->filterGroupList($group_list);
        $group_list = $this->sortGroupList($group_list);
        $group_list = $this->assignOrderID($group_list);
        $succ_count = $this->updateSummary($group_list);
        return $succ_count;
    }



    private function loadGameGroup(Bool $refresh = false):Array
    {
        $game_list = WikiGameModel::get();
        if ($refresh) { $game_list->fresh(); }
        if (empty($game_list)) { return []; }

        $wiki_game = [];
        foreach ($game_list as $row) {
            $group_id = $row['GroupID'];
            $wiki_game[$group_id]   = $wiki_game[$group_id]?? [];
            $wiki_game[$group_id][] = $row['Title'];
        }
        return $wiki_game;
    }

    private function findGameGroup(String $game):Array
    {
        foreach ($this->wiki_game as $group) {
            if (array_search($game, $group) !== false) {
                return $group;
            }
        }
        return [];
    }

    private function getSummaryData():Array
    {
        $data = SummaryModel::select('ID', 'Title','GroupID', 'OrderID', 'Country')
                    ->orderBy('GroupID', 'DESC')
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

    private function computeGameGroup(Array $pendingList):Array
    {
        $group_list = [];
        $last       = end($pendingList);
        $group_id   = $last['GroupID']?? 0;
        
        while (true) {
            $curr = array_shift($pendingList);
            if (!isset($curr)) { break; }

            // keep it original GroupID.
            if (!empty($curr['GroupID'])) {
                array_push($group_list, $curr);
                continue;
            }

            // find game's names from the group of wiki_game.
            $games = $this->findGameGroup($curr['Title']);
            array_unshift($games, $curr['Title']);
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

        $v_pattern = [
            '(\s(\d+|\w|[iv]{1,3})(\s|$))',
            '(\d+|[^a-z][iv]{1,3}+)\W{0,2}$',
            '(\d{4})'
        ];
        $v_pattern = sprintf('/%s/i', implode('|', $v_pattern));

        $title_len = strlen($title);
        $version   = preg_match($v_pattern, $title) > 0;
        foreach ($array as $key => $row) {
            if (strcasecmp($row['Title'], $title) === 0) {
                return $key;
            }
            if ($version || preg_match($v_pattern, $row['Title']) > 0) {
                continue;
            }
            if ($title_len > 15 && abs(strlen($row['Title']) - $title_len) < 3) {
                $dist = levenshtein($title, $row['Title']);
                if ($dist > -1 && $dist < 3) {
                    return $key;
                }
            }
        }
        return null;
    }
}