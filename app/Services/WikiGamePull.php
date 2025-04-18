<?php

namespace App\Services;

use App\Libraries\Curl as CurlLib;
use App\Models\WikiGame as WikiGameModel;
use App\Contracts\WikiGame as WikiGameContract;
use voku\helper\HtmlDomParser;

define('WIKI_URL', 'https://zh.wikipedia.org/wiki/{{YY}}年任天堂Switch游戏列表');

class WikiGamePull implements WikiGameContract
{
    public function getGameList():Array
    {
        $year = range(2017,2023);
        $list = [];
        foreach($year as $y) {
            $html = $this->getGameListFromWiki(str_replace('{{YY}}', strval($y), WIKI_URL));
            $list = array_merge($list, $this->parseWikiPage($html));
        }
        return array_column($list, null);
    }

    public function saveGameGroup(Array $gameList):Int
    {
        $save_num  = 0;
        $wiki_data = [];
        $wiki_game = new WikiGameModel();
        $wiki_game->truncate();
        
        foreach ($gameList as $index => $group) {
            if(empty($group) || !is_array($group)) { continue; }
            foreach ($group as $title) {
                $wiki_data[] = [
                    'GroupID' => $index + 1,
                    'Title'   => $title
                ];
                ++$save_num;
            }
        }
        $wiki_game->insert($wiki_data);
        return $save_num;
    }


    
    private function getGameListFromWiki($url):String
    {
        $Curl = new CurlLib();
        $Curl->setHeader(['accept-language: zh-TW,zh;q=0.9,en;q=0.8']);
        $response = $Curl->run($url);
        return $Curl->isSuccess($response)? $response['content'] : '';
    }

    private function parseWikiPage(String $html):Array
    {
        $dom    = HtmlDomParser::str_get_html($html);
        $target = $dom->find('table.wikitable > tbody > tr');
        $game_list = [];

        foreach ($target as $row) {
            if (empty($row->findOne('td')->Text())) { continue; } //跳過標題列
            $td = $row->findOne('td')->innerHtml();

            $game_names = [];
            if (stripos($td, '<li>') > -1) {
                //2022年以後的<td>還能再拆<li>
                $td = $row->find('td li');
                foreach($td as $key => $value) {
                    $game_names[] = $value->Text();
                }
            } else {
                $game_names[] = $row->findOne('td > a')->Text(); //部分遊戲名稱會有超連結
                $game_names[] = $row->findOne('td > span')->Text(); //部分遊戲名稱會用span包起來
                $td = preg_replace('/<a[^>]+>.+<\/a>/im', '', $td); //去除超連結
                $td = preg_replace('/<span[^>]+>.+<\/span>/im', '', $td); //去除span
                $td = explode('<br>', str_replace("\n", '<br>', $td));
                foreach ($td as $key => $value) {
                    if (!isset($td[$key])) { continue; }

                    $value = $this->formatGameName($value);
                    if (stripos($value, '*') > -1) { break; }
                    if (stripos($value, 'e.g.格式名') > -1) { break; }
                    
                    // merge next line if same language
                    if (!empty($td[$key+1])) {
                        $next = trim(strip_tags($td[$key+1]));
                        if ($this->getLanguage($value) === $this->getLanguage($next)) {
                            $value .= ' '.$next;
                            unset($td[$key+1]);
                        }
                    }
                    // merge to previous line if current str equals 'for Nintendo Switch'
                    if (strcasecmp($value, 'for Nintendo Switch') === 0) {
                        $pre_key = array_key_last($game_names);
                        $game_names[$pre_key] .= ' '.$value;
                        continue;
                    }
                    $game_names[] = $value;
                }
            }
            
            $game_names = array_filter($game_names, function($v){ return !empty(trim($v)); });
            $game_names = array_unique($game_names);
            if (empty($game_names)) { continue; }
            $game_list[] = $game_names;
        }
        return $game_list;
    }

    private function getLanguage(String $str):String
    {
        if (preg_match('/^[a-z0-9\s\-\.\(\),:;!]+$/i', $str) > 0) {
            return 'EN';
        }
        if (preg_match('/[\x{AC00}-\x{D7A3}]/u', $str) > 0) {
            return 'KR';
        }
        if (preg_match('/[\x{0800}-\x{4e00}]/u', $str) > 0) {
            return 'JP';
        }
        if (preg_match('/[\x4E00-\x9FFF]/', $str) > 0) {
            $ori_len = mb_strlen($str, 'utf-8');
            $cn_len  = mb_strlen(iconv('UTF-8', 'cp950//IGNORE', $str), 'cp950');
            return ($ori_len != $cn_len) ? 'CN' : 'TW';
        }
        return '';
    }

    private function formatGameName(String $str):String
    {
        $str = trim(strip_tags($str));
        $str = htmlspecialchars_decode($str);
        $str = str_replace(['™', '®'], '', $str);
        $str = preg_replace('/[\(（](.{3,}|暫名)[\)）]/iu', '', $str);
        $str = preg_replace('/\[\d+\]/iu', '', $str);

        // remove bracket both in head and tail
        if (preg_match('/^[\(（]([^\)）]+)[\)）]$/iu', $str, $m) > 0) {
            $str = trim($m[1]);
        }
        return $str;
    }
}