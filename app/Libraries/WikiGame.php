<?php

namespace App\Libraries;

use App\Libraries\Curl as CurlLib;
use voku\helper\HtmlDomParser;

define('WIKI_URL', 'https://zh.wikipedia.org/wiki/%E4%BB%BB%E5%A4%A9%E5%A0%82Switch%E6%B8%B8%E6%88%8F%E5%88%97%E8%A1%A8');

class WikiGame
{
    private $game_list = null;

    public function loadGameList():Void
    {
        $html = $this->getGameNameListFromWiki();
        $list = $this->parseWikiPage($html);
        $this->game_list = $list;
    }

    public function findGameGroup($game):Array
    {
        if (empty($this->game_list)) { return []; }

        foreach ($this->game_list as $group) {
            if (array_search($game, $group) !== false) {
                return $group;
            }
        }
        return [];
    }

    
    private function getGameNameListFromWiki():String
    {
        $Curl = new CurlLib();
        $Curl->setHeader(['accept-language: zh-TW,zh;q=0.9,en;q=0.8']);
        $response = $Curl->run(WIKI_URL);
        return $Curl->isSuccess($response)? $response['content'] : '';
    }

    private function parseWikiPage(String $html):Array
    {
        $dom    = HtmlDomParser::str_get_html($html);
        $target = $dom->find('table.wikitable tbody tr');
        foreach ($target as $row) {
            $td = trim($row->findOne('td')->innerHtml);
            $td = explode('<br>', str_replace("\n", '<br>', $td));
            if (reset($td) === 'e.g.格式名') { continue; }

            $game_names = [];
            foreach ($td as $key => $value) {
                if (!isset($td[$key])) { continue; }

                $value = $this->formatGameName($value);
                if (stripos($value, '*') !== false) { break; }
                
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
                if(empty($value)) { continue; }
                $game_names[] = $value;
            }
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