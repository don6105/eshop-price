<?php

namespace App\Services;

use App\Contracts\Translate as TranslateContract;

class Translate implements TranslateContract
{
    public function getGameNameList()
    {
        $html = $this->getGameNameListFromWiki();
        $list = $this->parseWikiPage($html);
        return $list;
    }

    private function getGameNameListFromWiki()
    {
        for ($req_index = 0; $req_index < 3; ++$req_index) {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => 'https://zh.wikipedia.org/wiki/%E4%BB%BB%E5%A4%A9%E5%A0%82Switch%E6%B8%B8%E6%88%8F%E5%88%97%E8%A1%A8',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_ENCODING       => "",
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_CUSTOMREQUEST  => "GET",
                CURLOPT_HTTPHEADER     => [
                    'accept-language: zh-TW,zh;q=0.9,en;q=0.8'
                ]
            ]);
            $response  = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error     = curl_error($curl);
            curl_close($curl);

            if ($http_code == 200) { return $response; }
        }
        return '';
    }

    private function parseWikiPage(String $html)
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $finder     = new \DOMXPath($dom);
        $class_name = 'wikitable';
        $node_list  = $finder->query("//table[contains(@class, '$class_name')]/tbody/tr/td[1]");
        $game_list  = [];
        foreach ($node_list as $node) {
            $td = $dom->saveHTML($node);
            $td = explode('<br>', $td);
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
                    $pre_key = array_keys($game_names);
                    $pre_key = end($pre_key);
                    $game_names[$pre_key] .= ' '.$value;
                    continue;
                }
                
                if(empty($value)) { continue; }
                $game_names[] = $value;
            }
            $game_list[] = array_unique($game_names);
        }
        return $game_list;
    }

    private function getLanguage(String $str)
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

    private function formatGameName(String $str)
    {
        $str = trim(strip_tags($str));
        $str = htmlspecialchars_decode($str);
        $str = preg_replace('/[\(（](.{3,}|暫名)[\)）]/iu', '', $str);
        $str = preg_replace('/\[\d+\]/iu', '', $str);

        // remove bracket both in head and tail
        if (preg_match('/^[\(（]([^\)）]+)[\)）]$/iu', $str, $m) > 0) {
            $str = trim($m[1]);
        }

        return $str;
    }
}