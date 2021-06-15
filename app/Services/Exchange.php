<?php

namespace App\Services;

use App\Contracts\Exchange as ExchangeContract;
use App\Services\Base as BaseService;
use App\Libraries\Curl as CurlLib;
use voku\helper\HtmlDomParser;

class Exchange extends BaseService implements ExchangeContract
{
    public function getExchangeRate()
    {
        $Curl     = new CurlLib();
        $response = $Curl->run('https://rate.bot.com.tw/xrt');
        $this->parseHtmlTable($response);
    }

    private function parseHtmlTable($response)
    {
        if (empty($response['content'])) {
            return [];
        }

        $dom = HtmlDomParser::str_get_html($response['content']);
        $target = $dom->find('table[title="牌告匯率"] .currency .print_show');
        $country = [];
        foreach ($target as $t) {
            $text      = html_entity_decode($t->innerText);
            $country[] = trim($text);
        }
        dd($country);
    }
}